<?php

namespace App\Http\Controllers\Api\AppointmentPayments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\AppointmentPaymentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PhonePeBridgeController extends Controller
{
    use ApiResponseTrait;

    private AppointmentPaymentService $paymentService;

    public function __construct(AppointmentPaymentService $appointmentPaymentService)
    {
        $this->paymentService = $appointmentPaymentService;
        // Service will be instantiated with gateway in methods
        // This keeps the controller gateway-agnostic
    }

    /**
     * Show payment page in WebView
     */
    public function showPaymentPage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|integer|exists:appointments,id',
            'app_return_url' => 'required|string',
            'gateway' => 'nullable|string|in:phonepe,razorpay', // Optional gateway selection
        ]);
        if ($validator->fails()) {
            return $this->renderError('Invalid request. ' . $validator->errors()->first());
        }

        $paymentResult =  Payment::where('appointment_id', $request->get('appointment_id'))->first();

        if (Payment::where('appointment_id', $request->get('appointment_id'))->where('status', 'paid')->exists()) {
            return $this->renderError('Appointment already booked');
        }

        try {
            $gateway = $request->input('gateway', 'phonepe');

            // Create payment record
            $payment = $this->paymentService->createPayment(
                $request->appointment_id,
                ['app_return_url' => $request->app_return_url]
            );


            // Store session data for callback
            $this->storeSessionData($payment, $request->app_return_url);

            // Render appropriate gateway view
            return $this->renderPaymentView($gateway, $payment, $request->app_return_url);
        } catch (\Exception $e) {

            Log::error('Payment page error', [
                'error' => $e->getMessage(),
                'appointment_id' => $request->appointment_id
            ]);

            return $this->renderError("Something went wrong");
        }
    }

    /**
     * Process payment from WebView
     */
    public function processPhonePePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|exists:payments,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $payment = Payment::with('appointment')->findOrFail($request->payment_id);

            // Instantiate service with correct gateway
            $this->paymentService = app(AppointmentPaymentService::class, [
                'gateway' => $payment->payment_method
            ]);

            // Store payment ID in session
            session(['bridge_payment_id' => $payment->id]);

            // Get callback and webhook URLs
            $callbackUrl = $this->getCallbackUrl($payment->payment_method);
            $webhookUrl = $this->getWebhookUrl($payment->payment_method);

            // Initiate payment
            $paymentResponse = $this->paymentService->initiatePayment($payment, [
                'redirectUrl' => $callbackUrl,
                'callbackUrl' => $webhookUrl
            ]);

            // Redirect to payment gateway
            return redirect()->away($paymentResponse['payment_url']);
        } catch (\Exception $e) {
            Log::error('Payment processing error', [
                'error' => $e->getMessage(),
                'payment_id' => $request->payment_id
            ]);

            return $this->renderError('Payment processing failed: ');
        }
    }

    /**
     * Handle callback from payment gateway
     */
    public function handleBridgeCallback(Request $request)
    {
        $transactionId = $this->extractTransactionId($request);

        Log::info('Payment callback received', [
            'transaction_id' => $transactionId,
            'request_data' => $request->all()
        ]);

        try {
            // Find payment by transaction ID
            $payment = Payment::where('transaction_id', $transactionId)->first();

            if (!$payment) {
                return $this->renderError('Payment not found', $this->getDefaultReturnUrl());
            }

            // Instantiate service with correct gateway
            $this->paymentService = app(AppointmentPaymentService::class, [
                'gateway' => $payment->payment_method
            ]);

            // Process callback
            $result = $this->paymentService->processCallback($transactionId);

            // Get return URL
            $appReturnUrl = $this->getAppReturnUrl($payment);

            // Render success or failure page
            if ($result['success']) {
                return $this->renderSuccess(
                    $result['payment'],
                    $result['appointment'],
                    $this->buildReturnUrl($appReturnUrl, $payment, 'COMPLETED')
                );
            } else {
                return $this->renderFailure(
                    $result['payment'],
                    $this->buildReturnUrl($appReturnUrl, $payment, 'FAILED'),
                    $result['message']
                );
            }
        } catch (\Exception $e) {
            Log::error('Callback processing error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            $returnUrl = session('app_return_url', $this->getDefaultReturnUrl());
            return $this->renderFailure(null, $returnUrl, 'Payment processing failed');
        }
    }

    /**
     * Handle webhook from payment gateway
     */
    public function handleWebhook(Request $request, string $gateway = null)
    {
        Log::info('Payment webhook received', [
            'gateway' => $gateway,
            'data' => $request->all()
        ]);

        try {
            // Instantiate service with gateway
            $this->paymentService = app(AppointmentPaymentService::class, ['gateway' => $gateway]);

            // Process webhook
            $result = $this->paymentService->processWebhook($request->all(), $gateway);

            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Webhook processed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'gateway' => $gateway,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'FAILURE',
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }

    // ========================================================================
    // HELPER METHODS - Controller-specific logic
    // ========================================================================

    /**
     * Store session data
     */
    private function storeSessionData(Payment $payment, string $appReturnUrl): void
    {
        session([
            'app_return_url' => $appReturnUrl,
            'appointment_id' => $payment->appointment_id,
            'payment_id' => $payment->id,
            'bridge_payment_id' => $payment->id
        ]);
    }

    /**
     * Extract transaction ID from request
     */
    private function extractTransactionId(Request $request): ?string
    {
        return $request->input('transactionId')
            ?? $request->input('merchantTransactionId')
            ?? $request->input('transaction_id')
            ?? $request->input('order_id');
    }

    /**
     * Get app return URL from payment metadata
     */
    private function getAppReturnUrl(Payment $payment): string
    {
        $paymentDetails = json_decode($payment->payment_details, true) ?? [];
        return $paymentDetails['app_return_url'] ?? session('app_return_url', $this->getDefaultReturnUrl());
    }

    /**
     * Build return URL with parameters
     */
    private function buildReturnUrl(string $baseUrl, Payment $payment, string $paymentState): string
    {
        // Handle Expo dev URLs
        $isExpoDevUrl = str_starts_with($baseUrl, 'exp://') || str_contains($baseUrl, 'expo-dev');

        if ($isExpoDevUrl && !str_starts_with($baseUrl, 'exp://')) {
            $devServer = session('dev_server', '127.0.0.1:8081');
            $baseUrl = "exp://{$devServer}/--/payment/callback";
        }

        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        $params = [
            'status' => strtolower($payment->status),
            'transaction_id' => $payment->transaction_id,
            'appointment_id' => $payment->appointment_id,
            'amount' => $payment->amount,
            'payment_state' => $paymentState
        ];

        $queryString = http_build_query($params);

        return $baseUrl . $separator . $queryString;
    }

    /**
     * Get callback URL for gateway
     */
    private function getCallbackUrl(string $gateway): string
    {
        return route('phonepe.bridge.payment.callback', ['gateway' => $gateway]);
    }

    /**
     * Get webhook URL for gateway
     */
    private function getWebhookUrl(string $gateway): string
    {
        return route('phonepe.bridge.webhook', ['gateway' => $gateway], true);
    }

    /**
     * Get default return URL
     */
    private function getDefaultReturnUrl(): string
    {
        return 'instaappoint://payment/callback?status=error';
    }

    // ========================================================================
    // VIEW RENDERING METHODS
    // ========================================================================

    /**
     * Render payment view based on gateway
     */
    private function renderPaymentView(string $gateway, Payment $payment, string $appReturnUrl)
    {
        $viewName = match ($gateway) {
            //'razorpay' => 'payment.razorpay-bridge',
            'phonepe' => 'payment.phonepe-bridge',
            default => 'payment.phonepe-bridge'
        };

        return view($viewName, [
            'appointment' => $payment->appointment,
            'payment' => $payment,
            'appReturnUrl' => $appReturnUrl
        ]);
    }

    /**
     * Render error page
     */
    private function renderError(string $message,  $returnUrl = null)
    {
        return response()->view('payment.error', [
            'message' => $message,
            'returnUrl' => $returnUrl ?? $this->getDefaultReturnUrl()
        ]);
    }

    /**
     * Render success page
     */
    private function renderSuccess(Payment $payment, $appointment, string $returnUrl)
    {
        return view('payment.bridge-success', [
            'payment' => $payment,
            'appointment' => $appointment,
            'returnUrl' => $returnUrl
        ]);
    }

    /**
     * Render failure page
     */
    private function renderFailure(?Payment $payment, string $returnUrl, string $errorMessage)
    {
        return view('payment.bridge-failed', [
            'payment' => $payment,
            'returnUrl' => $returnUrl,
            'errorMessage' => $errorMessage
        ]);
    }
}
