<?php

namespace App\Services\Payments;

use App\Enums\PlanEnum;
use App\Enums\SocialPlanEnum;
use App\Models\BaseSubscription;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SocialSubscription;
use App\Models\User;
use App\Services\PaymentGateways\Contracts\PaymentGatewayInterface;
use App\Traits\SendSmsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    use SendSmsTrait;

    private PaymentGatewayInterface $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    /* ==========================================================
     * CREATE SUBSCRIPTION
     * ========================================================== */

    public function createSubscription(array $data)
    {
        DB::beginTransaction();

        try {
            $plan = $this->resolvePlan($data['plan_name']);

            $modelClass = $this->resolveSubscriptionModel($plan['slug']);

            $user = $this->findOrCreateUser([
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'],
            ]);

            $paymentResponse = $this->paymentGateway->initiatePayment(
                $plan['discounted_price'],
                $user->id,
                [
                    'redirectUrl' => route('subscription.callback'),
                    'callbackUrl' => route('subscription.webhook'),
                    'returnUrl'   => route('subscription.razorpay.checkout'),
                ]
            );

            if (! $paymentResponse['success']) {
                throw new \Exception($paymentResponse['message'] ?? 'Payment initialization failed');
            }

            $transactionId = $paymentResponse['merchant_transaction_id'];

            $subscription = $this->createSubscriptionRecord(
                $modelClass,
                $user->id,
                $data['plan_name'],
                $plan['discounted_price'],
                $transactionId
            );

            $this->storeSessionData($data, $transactionId, $plan['discounted_price']);

            DB::commit();

            return [
                'success' => true,
                'payment_url' => $paymentResponse['payment_url'],
                'transaction_id' => $transactionId,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Subscription creation failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /* ==========================================================
     * CALLBACK
     * ========================================================== */

    public function processCallback($transactionId)
    {
        $subscription = $this->findSubscriptionByTransactionId($transactionId);

        if (! $subscription) {
            return ['success' => false, 'message' => 'Subscription not found'];
        }

        $status = $this->paymentGateway->checkPaymentStatus($transactionId);

        return $this->updateSubscriptionFromStatus($subscription, $status);
    }

    /* ==========================================================
     * WEBHOOK
     * ========================================================== */

    public function processWebhook(array $webhookData)
    {
        $transactionId = $webhookData['merchantTransactionId'] ?? null;

        $subscription = $this->findSubscriptionByTransactionId($transactionId);

        if (! $subscription) {
            return ['success' => false, 'message' => 'Subscription not found'];
        }

        $status = $this->paymentGateway->checkPaymentStatus($transactionId);

        return $this->updateSubscriptionFromStatus($subscription, $status, $webhookData);
    }

    /* ==========================================================
     * STATUS UPDATER (WORKS FOR BOTH MODELS)
     * ========================================================== */

    private function updateSubscriptionFromStatus(
        BaseSubscription $subscription,
        array $status,
        $webhookData = null
    ) {
        $paymentStatus = 'failed';
        $subscriptionStatus = null;

        if (!empty($status['success']) && $status['success']) {
            if (($status['paymentState'] ?? '') === 'COMPLETED') {
                $paymentStatus = 'completed';
                $subscriptionStatus = 'active';
            } elseif (($status['paymentState'] ?? '') === 'FAILED') {
                $paymentStatus = 'failed';
                $subscriptionStatus = 'failed';
            }
        }

        $responseData = $subscription->response
            ? json_decode($subscription->response, true)
            : [];

        if (!is_array($responseData)) {
            $responseData = [];
        }

        if ($webhookData) {
            $responseData['webhook'] = $webhookData;
            $responseData['webhook_status'] = $status;
            $responseData['webhook_received_at'] = now()->toIso8601String();
        } else {
            $responseData['responseData'] = $status['responseData'] ?? null;
        }

        $updateData = [
            'payment_status' => $paymentStatus,
            'response' => json_encode($responseData),
        ];

        if ($subscriptionStatus) {
            $updateData['status'] = $subscriptionStatus;
        }

        $subscription->update($updateData);

        // Optional SMS
        if ($paymentStatus === 'completed' && empty($responseData['sms_sent'])) {
            // $this->sendSms($subscription->user->mobile, 'subscription_success', []);
            $responseData['sms_sent'] = true;
            $subscription->update(['response' => json_encode($responseData)]);
        }

        return [
            'success' => $paymentStatus === 'completed',
            'status' => $paymentStatus,
            'subscription' => $subscription,
            'message' => $paymentStatus === 'completed'
                ? 'Payment completed successfully'
                : 'Payment not successful',
        ];
    }

    /* ==========================================================
     * HELPERS
     * ========================================================== */

    private function resolvePlan(string $planName)
    {
        // $plan = PlanEnum::getPlanByTitle($planName);

        // if (! $plan) {
        //     $plan = SocialPlanEnum::getPlanByTitle($planName);
        // }

        // if (! $plan || $plan['title'] !== $planName) {
        //     throw new \Exception('Selected plan not found.');
        // }

        // return $plan;

        $plan = Plan::where('slug', $planName)->first()->toArray();

        if (count($plan) === 0) {
            throw new \Exception('Selected plan not found.');
        }

        return $plan;
    }

    private function resolveSubscriptionModel(string $slug)
    {
        // $socialSlugs = SocialPlanEnum::getSlugs();

        // return in_array($slug, $socialSlugs)
        //     ? SocialSubscription::class
        //     : Subscription::class;

        $socialSlugs = Plan::where('type', 'social')->pluck('slug')->toArray();

        return in_array($slug, $socialSlugs)
            ? SocialSubscription::class
            : Subscription::class;
    }

    private function findSubscriptionByTransactionId($transactionId)
    {
        return Subscription::where('transaction_id', $transactionId)->first()
            ?? SocialSubscription::where('transaction_id', $transactionId)->first();
    }

    private function findOrCreateUser(array $data)
    {
        return User::firstOrCreate(
            ['mobile' => $data['mobile']],
            [
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => 'vendor',
                'password' => bcrypt('123456'),
            ]
        );
    }

    private function createSubscriptionRecord(
        string $modelClass,
        $userId,
        $planName,
        $amount,
        $transactionId
    ) {
        $subscription = new $modelClass();

        $subscription->user_id = $userId;
        $subscription->plan_name = $planName;
        $subscription->amount = $amount;
        $subscription->payment_status = 'pending';
        $subscription->payment_gateway = request()->get('payment_gateway') ?? 'phonepe';
        $subscription->transaction_id = $transactionId;
        $subscription->setDuration(1, 'year');
        $subscription->save();

        return $subscription;
    }

    private function storeSessionData($data, $transactionId, $amount)
    {
        session([
            'payment_transaction_id' => $transactionId,
            'payment_amount' => $amount,
            'payment_name' => $data['name'],
            'payment_email' => $data['email'],
            'payment_phone' => $data['mobile'],
            'plan_name' => $data['plan_name'],
        ]);
    }
}
