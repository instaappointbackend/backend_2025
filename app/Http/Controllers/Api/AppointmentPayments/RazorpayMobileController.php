<?php

namespace App\Http\Controllers\Api\AppointmentPayments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentGateways\RazorpayService;
use App\Services\Payments\AppointmentPaymentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RazorpayMobileController extends Controller
{
    use ApiResponseTrait;

    private AppointmentPaymentService $paymentService;

    private RazorpayService $razorpayService;

    public function __construct(
        AppointmentPaymentService $appointmentPaymentService,
        RazorpayService $razorpayService
    ) {
        $this->paymentService = $appointmentPaymentService;
        $this->razorpayService = $razorpayService;
    }

    /**
     * Create Razorpay order for mobile SDK
     * Mobile app calls this endpoint first
     */
    public function createOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|integer|exists:appointments,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            // Check if appointment already paid
            if (Payment::where('appointment_id', $request->appointment_id)
                ->where('status', 'paid')
                ->exists()
            ) {
                return $this->error(null, 'Appointment already booked', 400);
            }

            // Create payment record in database
            $payment = $this->paymentService->createPayment(
                $request->appointment_id,
                ['payment_method' => 'razorpay']
            );

            // Get user ID from appointment or authenticated user
            $userId = $payment->appointment->user_id ?? Auth::user()->id;

            // Create Razorpay order using service
            $orderResult = $this->razorpayService->initiatePayment(
                $payment->amount,
                $userId,
                [
                    'appointment_id' => $payment->appointment_id,
                    'payment_id' => $payment->id,
                    'type' => 'appointment',
                ]
            );

            if (! $orderResult['success']) {
                return $this->error(null, $orderResult['message'], 500);
            }

            // Update payment with Razorpay order ID
            $payment->update([
                'transaction_id' => $orderResult['order_id'],
                'payment_method' => 'razorpay',
                'payment_details' => json_encode([
                    'razorpay_order_id' => $orderResult['order_id'],
                    'merchant_transaction_id' => $orderResult['merchant_transaction_id'],
                    'created_at' => now()->toDateTimeString(),
                ]),
            ]);

            Log::info('Razorpay order created', [
                'order_id' => $orderResult['order_id'],
                'payment_id' => $payment->id,
                'appointment_id' => $payment->appointment_id,
            ]);

            // Return data for mobile SDK
            return $this->success([
                'order_id' => $orderResult['order_id'],
                'amount' => $orderResult['amount'], // Amount in paise
                'currency' => $orderResult['currency'],
                'payment_id' => $payment->id,
                'key_id' => $orderResult['key_id'],
                'appointment' => $payment->appointment,
            ], 'Order created successfully');
        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed', [
                'error' => $e->getMessage(),
                'appointment_id' => $request->appointment_id,
            ]);

            return $this->error(null, 'Failed to create payment order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify payment after mobile SDK completion
     * Mobile app calls this after user completes payment in SDK
     */
    public function verifyPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            // Find payment by order ID
            $payment = Payment::where('transaction_id', $request->razorpay_order_id)->first();

            if (! $payment) {
                return $this->error(null, 'Payment not found', 404);
            }

            // Verify signature using service
            $verificationResult = $this->razorpayService->verifyPaymentSignature(
                $request->razorpay_order_id,
                $request->razorpay_payment_id,
                $request->razorpay_signature
            );

            if (! $verificationResult['success'] || ! $verificationResult['verified']) {
                Log::error('Payment signature verification failed', [
                    'payment_id' => $payment->id,
                    'order_id' => $request->razorpay_order_id,
                    'message' => $verificationResult['message'] ?? 'Verification failed',
                ]);

                $payment->update(['status' => 'failed']);

                return $this->error(null, 'Payment verification failed', 400);
            }

            // Fetch payment details using service
            $paymentDetailsResult = $this->razorpayService->getPaymentDetails($request->razorpay_payment_id);

            // Update payment record
            $paymentDetails = json_decode($payment->payment_details, true) ?? [];
            $paymentDetails['razorpay_payment_id'] = $request->razorpay_payment_id;
            $paymentDetails['razorpay_signature'] = $request->razorpay_signature;
            $paymentDetails['verified_at'] = now()->toDateTimeString();

            if ($paymentDetailsResult['success']) {
                $paymentDetails['payment_method'] = $paymentDetailsResult['payment']['method'] ?? null;
                $paymentDetails['email'] = $paymentDetailsResult['payment']['email'] ?? null;
                $paymentDetails['contact'] = $paymentDetailsResult['payment']['contact'] ?? null;
            }

            $payment->update([
                'status' => 'paid',
                'payment_details' => json_encode($paymentDetails),
            ]);

            // Update appointment status
            if ($payment->appointment) {
                $payment->appointment->update(['status' => 'confirmed', 'payment_status' => 'paid']);
                $this->paymentService->sendPaymentSuccessNotifications($payment->appointment);
            }

            Log::info('Payment verified successfully', [
                'payment_id' => $payment->id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
            ]);

            return $this->success([
                'payment' => $payment->fresh(),
                'appointment' => $payment->appointment,
                'status' => 'SUCCESS',
            ], 'Payment verified successfully');
        } catch (\Exception $e) {
            Log::error('Payment verification error', [
                'error' => $e->getMessage(),
                'order_id' => $request->razorpay_order_id,
            ]);

            if (isset($payment)) {
                $payment->update(['status' => 'failed']);
            }

            return $this->error(null, 'Payment verification failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Handle payment failure from mobile app
     */
    public function handleFailure(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'razorpay_order_id' => 'required|string',
            'error_code' => 'nullable|string',
            'error_description' => 'nullable|string',
            'error_source' => 'nullable|string',
            'error_step' => 'nullable|string',
            'error_reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $payment = Payment::where('transaction_id', $request->razorpay_order_id)->first();

            if (! $payment) {
                return $this->error(null, 'Payment not found', 404);
            }

            // Update payment as failed
            $paymentDetails = json_decode($payment->payment_details, true) ?? [];
            $paymentDetails['error_code'] = $request->error_code;
            $paymentDetails['error_description'] = $request->error_description;
            $paymentDetails['error_source'] = $request->error_source;
            $paymentDetails['error_step'] = $request->error_step;
            $paymentDetails['error_reason'] = $request->error_reason;
            $paymentDetails['failed_at'] = now()->toDateTimeString();

            $payment->update([
                'status' => 'failed',
                'payment_details' => json_encode($paymentDetails),
            ]);

            Log::warning('Payment failed', [
                'payment_id' => $payment->id,
                'order_id' => $request->razorpay_order_id,
                'error' => $request->error_description,
            ]);

            return $this->success([
                'payment' => $payment,
                'status' => 'FAILED',
            ], 'Payment failure recorded');
        } catch (\Exception $e) {
            Log::error('Payment failure handling error', [
                'error' => $e->getMessage(),
                'order_id' => $request->razorpay_order_id,
            ]);

            return $this->error(null, 'Failed to process payment failure', 500);
        }
    }

    /**
     * Razorpay webhook handler
     * Razorpay sends webhooks for payment events
     */
    public function handleWebhook(Request $request)
    {
        Log::info('Razorpay webhook received', [
            'event' => $request->input('event'),
            'data' => $request->all(),
        ]);

        try {
            $signature = $request->header('X-Razorpay-Signature');

            // Process webhook using service
            $webhookResult = $this->razorpayService->processWebhook(
                $request->all(),
                $signature
            );

            if (! $webhookResult['success']) {
                Log::error('Webhook verification failed', [
                    'message' => $webhookResult['message'],
                ]);

                return response()->json(['status' => 'error'], 400);
            }

            // Handle different webhook events
            $event = $webhookResult['event'];

            switch ($event) {
                case 'payment.authorized':
                case 'payment.captured':
                    $this->handlePaymentSuccess($webhookResult);
                    break;

                case 'payment.failed':
                    $this->handlePaymentFailed($webhookResult);
                    break;

                case 'order.paid':
                    $this->handleOrderPaid($webhookResult);
                    break;

                default:
                    Log::info('Unhandled webhook event', ['event' => $event]);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'event' => $request->input('event'),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(Request $request, $paymentId)
    {
        try {
            $payment = Payment::with('appointment')->findOrFail($paymentId);

            // If payment has razorpay_payment_id, fetch latest status
            $paymentDetails = json_decode($payment->payment_details, true) ?? [];
            $razorpayPaymentId = $paymentDetails['razorpay_payment_id'] ?? null;

            if ($razorpayPaymentId && $payment->status === 'pending') {
                $statusResult = $this->razorpayService->getPaymentDetail($razorpayPaymentId);

                if ($statusResult['success']) {
                    // Update payment status based on Razorpay status
                    $paymentState = $statusResult['paymentState'];

                    if ($paymentState === 'COMPLETED' && $payment->status !== 'paid') {
                        $payment->update(['status' => 'paid']);

                        if ($payment->appointment) {
                            $payment->appointment->update(['status' => 'confirmed']);
                        }
                    } elseif ($paymentState === 'FAILED' && $payment->status !== 'failed') {
                        $payment->update(['status' => 'failed']);
                    }

                    $payment->refresh();
                }
            }

            return $this->success([
                'payment' => $payment,
                'appointment' => $payment->appointment,
                'status' => strtoupper($payment->status),
            ], 'Payment fetch successfully');
        } catch (\Exception $e) {
            return $this->error(null, 'Payment not found', 404);
        }
    }

    // ========================================================================
    // PRIVATE HELPER METHODS
    // ========================================================================

    /**
     * Handle successful payment webhook
     */
    private function handlePaymentSuccess(array $webhookResult): void
    {
        $orderId = $webhookResult['order_id'] ?? null;
        $paymentId = $webhookResult['payment_id'] ?? null;

        if (! $orderId) {
            return;
        }

        $payment = Payment::where('transaction_id', $orderId)->first();

        if ($payment && $payment->status !== 'paid') {
            $paymentDetails = json_decode($payment->payment_details, true) ?? [];
            $paymentDetails['razorpay_payment_id'] = $paymentId;
            $paymentDetails['webhook_status'] = $webhookResult['status'];
            $paymentDetails['webhook_received_at'] = now()->toDateTimeString();

            $payment->update([
                'status' => 'paid',
                'payment_details' => json_encode($paymentDetails),
            ]);

            if ($payment->appointment) {
                $payment->appointment->update(['status' => 'confirmed', 'payment_status' => 'completed']);
            }

            Log::info('Payment updated via webhook', [
                'payment_id' => $payment->id,
                'razorpay_payment_id' => $paymentId,
            ]);
        }
    }

    /**
     * Handle failed payment webhook
     */
    private function handlePaymentFailed(array $webhookResult): void
    {
        $orderId = $webhookResult['order_id'] ?? null;

        if (! $orderId) {
            return;
        }

        $payment = Payment::where('transaction_id', $orderId)->first();

        if ($payment && $payment->status === 'pending') {
            $paymentDetails = json_decode($payment->payment_details, true) ?? [];
            $paymentDetails['webhook_status'] = $webhookResult['status'];
            $paymentDetails['webhook_received_at'] = now()->toDateTimeString();

            $payment->update([
                'status' => 'failed',
                'payment_details' => json_encode($paymentDetails),
            ]);

            Log::warning('Payment failed via webhook', [
                'payment_id' => $payment->id,
            ]);
        }
    }

    /**
     * Handle order paid webhook
     */
    private function handleOrderPaid(array $webhookResult): void
    {
        $orderId = $webhookResult['order_id'] ?? null;

        if (! $orderId) {
            return;
        }

        $payment = Payment::where('transaction_id', $orderId)->first();

        if ($payment && $payment->status !== 'paid') {
            $paymentDetails = json_decode($payment->payment_details, true) ?? [];
            $paymentDetails['webhook_received_at'] = now()->toDateTimeString();

            $payment->update([
                'status' => 'paid',
                'payment_details' => json_encode($paymentDetails),
            ]);

            if ($payment->appointment) {
                $payment->appointment->update(['status' => 'confirmed']);
            }

            Log::info('Order marked as paid via webhook', [
                'payment_id' => $payment->id,
            ]);
        }
    }
}
