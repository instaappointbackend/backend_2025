<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Services\PaymentGateways\RazorpayService;
use App\Services\Payments\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    private $subscriptionService;

    private $razorpayService;

    public function __construct(SubscriptionService $subscriptionService, RazorpayService $razorpayService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->razorpayService = $razorpayService;
    }

    public function index(Request $request)
    {
        if ($request->status === 'error') {
            session()->flash('error', $request->message);
        }

        $selected_plan = $request->get('plan');
        $normal_plan = [
            'basic',
            'standard',
            'super_saving'
        ];
        if (in_array($selected_plan, $normal_plan)) {
            $plans = Plan::where('type', 'normal')->get();
        } else {
            $plans = Plan::where('type', 'social')->get();
        }

        return view('subscriptions.index', compact('selected_plan', 'plans'));
    }

    public function razorpayCheckout(Request $request)
    {
        return view('subscriptions.razorpayCheckout');
    }

    public function razorpayPaymentFailed(Request $request)
    {

        $transactionId = $request->input('razorpay_order_id');

        $subscription = Subscription::where('transaction_id', $transactionId)->first();

        if (! $subscription) {
            Log::warning('Subscription not found for failed payment', [
                'transaction_id' => $transactionId,
            ]);

            return;
        }

        $result = $this->razorpayService->getPaymentDetail($transactionId);

        if ($result['paymentState'] === 'FAILED') {
            $subscription->update([
                'payment_status' => 'failed',
                'razorpay_payment_id' => $request->get('razorpay_payment_id'),
            ]);

            // 4. Clear session
            session()->forget([
                'order_id',
                'payment_id',
                // 'signature',
            ]);

            return redirect()->route('subscription.status', [
                'status' => 'failed',
                'message' => 'Payment Failed',
            ]);
        }
    }

    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'plan_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mobile' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'payment_gateway' => 'required|in:razorpay,phonepe',
        ]);

        try {
            $result = $this->subscriptionService->createSubscription($validated);

            return redirect()->away($result['payment_url']);
        } catch (\Throwable $th) {
            Log::error('Subscription failed', [
                'error' => $th->getMessage(),
            ]);

            return back()->with('error', $th->getMessage());
        }
    }

    public function callback(Request $request)
    {

        $transactionId = $request->input('transactionId', session('payment_transaction_id'));

        // set Razorpay data
        $paymentGateway = $request->get('payment_gateway');
        if ($paymentGateway === 'razorpay') {
            session([
                'order_id' => $request->get('razorpay_order_id'),
                'payment_id' => $request->get('razorpay_payment_id'),
                'signature' => $request->get('razorpay_signature'),
            ]);
        }

        Log::info($paymentGateway . ' callback received for subscription', [
            'transaction_id' => $transactionId,
            'data' => $request->all(),
        ]);

        if (! $transactionId) {
            return redirect()->route('subscription.status', [
                'status' => 'error',
                'message' => 'Invalid transaction ID',
            ]);
        }

        try {

            $result = $this->subscriptionService->processCallback($transactionId);
            // 4. Clear session
            session()->forget([
                'order_id',
                'payment_id',
                'signature',
            ]);

            if ($result['success']) {
                return redirect()->route('subscription.status', ['status' => 'success']);
            }

            return redirect()->route('subscription.status', [
                'status' => 'failed',
                'message' => $result['message'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Subscription callback failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('subscription.status', [
                'status' => 'error',
                'message' => 'An unexpected error occurred. Please contact support',
            ]);
        }
    }

    public function subscriptionStatus(Request $request, $status)
    {
        $slug = session('slug');
        if ($status === 'success') {
            $paymentDetails = [
                'transaction_id' => session('payment_transaction_id'),
                'amount' => session('payment_amount'),
                'plan_name' => session('plan_name'),
                'name' => session('payment_name'),
                'email' => session('payment_email'),
                'phone' => session('payment_phone'),
            ];

            return view('subscriptions.success', compact('paymentDetails', 'status', 'slug'));
        }

        if ($status === 'failed' || $status === 'error') {
            $message = $request->get('message', 'Payment failed. Please try again.');

            return view('subscriptions.failed', compact('message', 'slug'));
        }

        return redirect()->route('subscribe.index');
    }


    public function webhook(Request $request)
    {
        Log::info('Webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        try {

            // // Detect gateway
            // if ($this->isRazorpayWebhook($request)) {
            //     $result = $this->subscriptionService->processWebhook($request->all());
            // }

            // if ($this->isPhonePeWebhook($request)) {
            //     return $this->subscriptionService->processWebhook($request->all());
            // }

            // return response()->json(['message' => 'Unknown webhook source'], 400);
        } catch (\Throwable $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Webhook error'], 500);
        }
    }

    private function isRazorpayWebhook(Request $request)
    {
        return $request->header('X-Razorpay-Signature') !== null;
    }

    private function isPhonePeWebhook(Request $request)
    {
        return isset($request->event) || isset($request->payload);
    }
}
