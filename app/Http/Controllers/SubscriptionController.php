<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SocialSubscription;
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

            return back()->with('error', 'Something went wrong');
        }
    }

    private function checkSubscriptionProcess($transactionId)
    {
        return Subscription::where('transaction_id', $transactionId)->first()
            ?? SocialSubscription::where('transaction_id', $transactionId)->first();
    }

    public function callback(Request $request)
    {

        $transactionId = $request->input('transactionId', session('payment_transaction_id'));

        // set Razorpay data
        $paymentGateway = $request->get('payment_gateway');
        if ($paymentGateway === 'razorpay') {
            $orderId =  $request->get('razorpay_order_id');
            session([
                'order_id' => $orderId,
                'payment_id' => $request->get('razorpay_payment_id'),
                'signature' => $request->get('razorpay_signature'),
            ]);

            $data = $this->checkSubscriptionProcess($orderId);

            if ($data->payment_status === 'payment_status') {
                Log::info($paymentGateway . '  order process successfully ' . $orderId);
            }

            return;
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
        // dd($slug);
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
            if ($this->isRazorpayWebhook($request)) {
                $result = $this->subscriptionService->processWebhook($request->all());
            }

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


    /**
     * Handle payment callback (GET request with query parameters)
     */
    public function razorPayCallback(Request $request)
    {
        try {
            //https://newinstaapp.test/subscription/payment/razorpay/callback?transactionId=order_SZQ0bBib1lAWO9&razorpay_order_id=order_SZQ0bBib1lAWO9&razorpay_payment_id=pay_SZQ0lrkwOFxS4J&razorpay_signature=44fe285ab8b83fc75d11164b0e2781ef8a5c6ce5e59d9706a7c9a36ed1e74e03&payment_gateway=razorpay


            //https://newinstaapp.test/subscription/payment/razorpay/callback?transactionId=order_SZR0KeaZ4LrsyJ&status=failed&error=Payment+was+unsuccessful+due+to+a+temporary+issue.+If+amount+got+deducted%2C+it+will+be+refunded+within+5-7+working+days.&payment_gateway=razorpay

            // Get transaction ID from query parameter
            $transactionId = $request->input('transactionId')
                ?? $request->input('razorpay_order_id')
                ?? $request->input('transaction_id');
            session([
                'order_id' => $transactionId,
                'payment_id' => $request->get('razorpay_payment_id'),
                'signature' => $request->get('razorpay_signature'),
            ]);


            if (!$transactionId) {
                Log::error('Callback missing transaction ID', [
                    'request' => $request->all()
                ]);

                return redirect()->route('subscription.status', [
                    'status' => 'failed',
                    'message' => 'Invalid payment reference'
                ]);
            }

            Log::info('Processing subscription callback', [
                'transaction_id' => $transactionId,
                'payment_gateway' => $request->input('payment_gateway', 'unknown'),
                'all_params' => $request->all()
            ]);

            // Find subscription
            $subscription = Subscription::where('transaction_id', $transactionId)->first();

            if (!$subscription) {
                Log::error('Subscription not found', [
                    'transaction_id' => $transactionId
                ]);

                return redirect()->route('subscription.status', [
                    'status' => 'failed',
                    'message' => 'Subscription not found'
                ]);
            }

            // If already processed, redirect to success
            if ($subscription->payment_status === 'completed' || $subscription->status === 'active') {
                Log::info('Subscription already completed', [
                    'transaction_id' => $transactionId,
                    'status' => $subscription->status
                ]);

                return view('subscription.success', [
                    'subscription' => $subscription,
                    'message' => 'Your subscription is already active!'
                ]);
            }

            // Process callback through service
            $result = $this->subscriptionService->processCallback($transactionId);

            if ($result['success']) {
                Log::info('Subscription callback successful', [
                    'transaction_id' => $transactionId,
                    'subscription_id' => $result['subscription']->id
                ]);

                // Store payment details if provided
                if ($request->has('razorpay_payment_id')) {
                    $responseData = json_decode($result['subscription']->response, true) ?? [];
                    $responseData['razorpay_payment_id'] = $request->input('razorpay_payment_id');
                    $responseData['razorpay_signature'] = $request->input('razorpay_signature');
                    $responseData['callback_received_at'] = now()->toIso8601String();

                    $result['subscription']->update([
                        'response' => json_encode($responseData)
                    ]);
                }
                //dd($result);
                // return view('subscriptions.success', [
                //     'subscription' => $result['subscription'],
                //     'status' => $result['success'],
                //     'message' => $result['message']
                // ]);

                if ($result['success']) {
                    return redirect()->route('subscription.status', ['status' => 'success']);
                }

                return redirect()->route('subscription.status', [
                    'status' => 'failed',
                    'message' => $result['message'],
                ]);
            } else {
                Log::warning('Subscription callback failed', [
                    'transaction_id' => $transactionId,
                    'message' => $result['message']
                ]);

                // return view('subscriptions.failed', [
                //     'message' => $result['message'] ?? 'Payment verification failed',
                //     'transaction_id' => $transactionId
                // ]);
                return redirect()->route('subscription.status', [
                    'status' => 'failed',
                    'message' => $result['message'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Subscription callback error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return redirect()->route('subscription.status', [
                'status' => 'error',
                'message' => 'An unexpected error occurred. Please contact support',
            ]);
        }
    }

    /**
     * Check payment status via AJAX
     */
    public function checkStatus(Request $request)
    {
        try {
            $orderId = $request->input('order_id');

            if (!$orderId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order ID is required'
                ], 400);
            }

            $subscription = Subscription::where('transaction_id', $orderId)->first();

            if (!$subscription) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Subscription not found'
                ], 404);
            }

            // If already completed
            if ($subscription->payment_status === 'completed' || $subscription->status === 'active') {
                $response = json_decode($subscription->response, true) ?? [];

                return response()->json([
                    'status' => 'paid',
                    'message' => 'Payment completed',
                    'razorpay_order_id' => $subscription->transaction_id,
                    'razorpay_payment_id' => $response['razorpay_payment_id'] ?? null,
                ]);
            }

            // If failed
            if ($subscription->payment_status === 'failed' || $subscription->status === 'failed') {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Payment failed'
                ]);
            }

            // For pending, check with gateway
            try {
                $result = $this->subscriptionService->processCallback($orderId);

                if ($result['success']) {
                    $response = json_decode($result['subscription']->response, true) ?? [];

                    return response()->json([
                        'status' => 'paid',
                        'message' => 'Payment completed',
                        'razorpay_order_id' => $result['subscription']->transaction_id,
                        'razorpay_payment_id' => $response['razorpay_payment_id'] ?? null,
                    ]);
                } else {
                    return response()->json([
                        'status' => 'pending',
                        'message' => 'Payment is still being processed'
                    ]);
                }
            } catch (\Exception $e) {
                // If gateway check fails, return pending
                Log::warning('Status check failed, returning pending', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'status' => 'pending',
                    'message' => 'Payment is being verified'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Status check failed', [
                'order_id' => $request->input('order_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check payment status'
            ], 500);
        }
    }

    /**
     * Show status page
     */
    public function showStatus(Request $request)
    {
        $status = $request->input('status', 'pending');
        $message = $request->input('message', '');

        return view('subscription.status', [
            'status' => $status,
            'message' => $message
        ]);
    }
}
