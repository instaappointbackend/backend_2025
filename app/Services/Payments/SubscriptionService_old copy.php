<?php

namespace App\Services\Payments;

use App\Enums\PlanEnum;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PaymentGateways\PhonePeService;
use App\Traits\SendSmsTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    use SendSmsTrait;

    private $phonePeService;

    public function __construct(PhonePeService $phonePeService)
    {
        $this->phonePeService = $phonePeService;
    }

    /**
     * Create subscription and initiate payment
     */
    public function createSubscription(array $data)
    {
        DB::beginTransaction();

        try {
            // Validate plan
            $plan = PlanEnum::getPlanByTitle($data['plan_name']);

            if ($plan['title'] !== $data['plan_name']) {
                throw new \Exception('Selected plan not found. Please choose a valid plan from the available options');
            }

            // Find or create user
            $user = $this->findOrCreateUser([
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile']
            ]);

            // Initiate payment
            $paymentResponse = $this->phonePeService->initiatePayment(
                $plan['discounted_price'],
                $user->id,
                [
                    'redirectUrl' => route('subscription.callback'),
                    'callbackUrl' => route('subscription.webhook')
                ]
            );

            if (!$paymentResponse['success']) {
                throw new \Exception($paymentResponse['message'] ?? 'Failed to initialize payment');
            }

            $transactionId = $paymentResponse['merchant_transaction_id'];

            // Create subscription
            $subscription = $this->createSubscriptionRecord(
                $user->id,
                $data['plan_name'],
                $plan['discounted_price'],
                $transactionId
            );

            // Store session data
            $this->storeSessionData($data, $transactionId, $plan['discounted_price']);

            DB::commit();

            return [
                'success' => true,
                'payment_url' => $paymentResponse['payment_url'],
                'transaction_id' => $transactionId
            ];
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Subscription creation failed', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            throw $th;
        }
    }

    /**
     * Process subscription callback
     */
    public function processCallback($transactionId)
    {
        try {
            if (!$transactionId) {
                return [
                    'success' => false,
                    'message' => 'Missing transaction ID'
                ];
            }

            $subscription = Subscription::with('user')
                ->where('transaction_id', $transactionId)
                ->first();

            if (!$subscription) {
                return [
                    'success' => false,
                    'message' => 'Subscription not found'
                ];
            }

            $status = $this->phonePeService->checkPaymentStatus($transactionId);

            return $this->updateSubscriptionFromStatus($subscription, $status);
        } catch (\Throwable $e) {
            Log::error('Subscription callback processing failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Process subscription webhook
     */
    public function processWebhook(array $webhookData)
    {
        try {
            $transactionId = $webhookData['merchantTransactionId'] ?? null;

            if (!$transactionId) {
                return [
                    'success' => false,
                    'message' => 'Missing transaction ID'
                ];
            }

            $subscription = Subscription::with('user')
                ->where('transaction_id', $transactionId)
                ->first();

            if (!$subscription) {
                return [
                    'success' => false,
                    'message' => 'Subscription not found'
                ];
            }

            $status = $this->phonePeService->checkPaymentStatus($transactionId);

            // Pass webhook data to the updater
            return $this->updateSubscriptionFromStatus($subscription, $status, $webhookData);
        } catch (\Throwable $e) {
            Log::error('Subscription webhook processing failed', [
                'webhook_data' => $webhookData,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Common subscription updater
     */
    private function updateSubscriptionFromStatus(Subscription $subscription, array $status, array $webhookData = null)
    {
        $paymentStatus = 'pending';
        $subscriptionStatus = null;

        if (!empty($status['success']) && $status['success']) {
            switch ($status['paymentState'] ?? '') {
                case 'COMPLETED':
                    $paymentStatus = 'completed';
                    $subscriptionStatus = 'active';
                    break;
                case 'FAILED':
                    $paymentStatus = 'failed';
                    $subscriptionStatus = 'failed';
                    break;
            }
        } else {
            $paymentStatus = 'failed';
            $subscriptionStatus = 'failed';
        }

        // Merge response data
        $responseData = $subscription->response ? json_decode($subscription->response, true) : [];
        if ($webhookData) {
            $responseData['webhook'] = $webhookData;
            $responseData['webhook_status'] = $status;
            $responseData['webhook_received_at'] = now()->toIso8601String();
        } else {
            $responseData['responseData'] = $status['responseData'] ?? null;
        }

        $updateData = [
            'payment_status' => $paymentStatus,
            'response' => json_encode($responseData)
        ];

        if ($subscriptionStatus) {
            $updateData['status'] = $subscriptionStatus;
        }

        $subscription->update($updateData);

        // Send SMS on completion if not already sent
        if ($paymentStatus === 'completed' && empty($responseData['sms_sent'])) {
            //$this->sendSms($subscription->user->mobile, 'subscription_success', []);
            $responseData['sms_sent'] = true;
            $subscription->update(['response' => json_encode($responseData)]);
        }

        return [
            'success' => $paymentStatus === 'completed',
            'status' => $paymentStatus,
            'subscription' => $subscription,
            'message' => $paymentStatus === 'completed' ? 'Payment completed successfully' : 'Payment not successful'
        ];
    }


    /**
     * Find or create user
     */
    private function findOrCreateUser(array $userData)
    {
        $user = User::where('mobile', $userData['mobile'])->first();

        if (!$user) {
            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'mobile' => $userData['mobile'],
                'role' => 'vendor',
                'password' => bcrypt('123456'),
            ]);
        }

        return $user;
    }

    /**
     * Create subscription record
     */
    private function createSubscriptionRecord($userId, $planName, $amount, $transactionId)
    {
        $subscription = new Subscription();
        $subscription->user_id = $userId;
        $subscription->plan_name = $planName;
        $subscription->amount = $amount;
        $subscription->payment_status = 'pending';
        $subscription->payment_method = 'phonepe';
        $subscription->transaction_id = $transactionId;
        $subscription->setDuration(1, 'year');
        $subscription->save();

        return $subscription;
    }

    /**
     * Store session data
     */
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
