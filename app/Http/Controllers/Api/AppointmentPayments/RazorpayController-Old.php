<?php

namespace App\Http\Controllers\Api\AppointmentPayments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\AppointmentPaymentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Razorpay\Api\Api;

class RazorpayMobileController extends Controller
{
    use ApiResponseTrait;

    private AppointmentPaymentService $paymentService;
    private Api $razorpay;

    public function __construct(AppointmentPaymentService $appointmentPaymentService)
    {
        $this->paymentService = $appointmentPaymentService;

        // Initialize Razorpay API
        $this->razorpay = new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );
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

            // Create Razorpay order
            $orderData = [
                'receipt' => 'APPT_' . $payment->appointment_id . '_' . time(),
                'amount' => $payment->amount * 100, // Amount in paise
                'currency' => 'INR',
                'notes' => [
                    'appointment_id' => $payment->appointment_id,
                    'payment_id' => $payment->id,
                ]
            ];

            $razorpayOrder = $this->razorpay->order->create($orderData);

            // Update payment with Razorpay order ID
            $payment->update([
                'transaction_id' => $razorpayOrder->id,
                'payment_details' => json_encode([
                    'razorpay_order_id' => $razorpayOrder->id,
                    'receipt' => $orderData['receipt'],
                    'created_at' => now()->toDateTimeString()
                ])
            ]);

            Log::info('Razorpay order created', [
                'order_id' => $razorpayOrder->id,
                'payment_id' => $payment->id,
                'appointment_id' => $payment->appointment_id
            ]);

            // Return data for mobile SDK
            return $this->success([
                'order_id' => $razorpayOrder->id,
                'amount' => $payment->amount,
                'currency' => 'INR',
                'payment_id' => $payment->id,
                'key' => config('services.razorpay.key'),
                'appointment' => $payment->appointment,
            ], 'Order created successfully');
        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed', [
                'error' => $e->getMessage(),
                'appointment_id' => $request->appointment_id
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

            if (!$payment) {
                return $this->error(null, 'Payment not found', 404);
            }

            // Verify signature
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature
            ];

            $this->razorpay->utility->verifyPaymentSignature($attributes);

            // Signature verified - fetch payment details
            $razorpayPayment = $this->razorpay->payment->fetch($request->razorpay_payment_id);

            // Update payment record
            $paymentDetails = json_decode($payment->payment_details, true) ?? [];
            $paymentDetails['razorpay_payment_id'] = $request->razorpay_payment_id;
            $paymentDetails['razorpay_signature'] = $request->razorpay_signature;
            $paymentDetails['payment_method'] = $razorpayPayment->method;
            $paymentDetails['verified_at'] = now()->toDateTimeString();

            $payment->update([
                'status' => 'paid',
                'payment_details' => json_encode($paymentDetails)
            ]);

            // Update appointment status if needed
            if ($payment->appointment) {
                $payment->appointment->update(['status' => 'confirmed']);
            }

            Log::info('Payment verified successfully', [
                'payment_id' => $payment->id,
                'razorpay_payment_id' => $request->razorpay_payment_id
            ]);

            return $this->success([
                'payment' => $payment->fresh(),
                'appointment' => $payment->appointment,
                'status' => 'SUCCESS'
            ], 'Payment verified successfully');
        } catch (\Razorpay\Api\Errors\SignatureVerificationError $e) {
            Log::error('Payment signature verification failed', [
                'error' => $e->getMessage(),
                'order_id' => $request->razorpay_order_id
            ]);

            // Update payment as failed
            if (isset($payment)) {
                $payment->update(['status' => 'failed']);
            }

            return $this->error(null, 'Payment verification failed', 400);
        } catch (\Exception $e) {
            Log::error('Payment verification error', [
                'error' => $e->getMessage(),
                'order_id' => $request->razorpay_order_id
            ]);

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
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        try {
            $payment = Payment::where('transaction_id', $request->razorpay_order_id)->first();

            if (!$payment) {
                return $this->error(null, 'Payment not found', 404);
            }

            // Update payment as failed
            $paymentDetails = json_decode($payment->payment_details, true) ?? [];
            $paymentDetails['error_code'] = $request->error_code;
            $paymentDetails['error_description'] = $request->error_description;
            $paymentDetails['failed_at'] = now()->toDateTimeString();

            $payment->update([
                'status' => 'failed',
                'payment_details' => json_encode($paymentDetails)
            ]);

            Log::warning('Payment failed', [
                'payment_id' => $payment->id,
                'order_id' => $request->razorpay_order_id,
                'error' => $request->error_description
            ]);

            return $this->success([
                'payment' => $payment,
                'status' => 'FAILED'
            ], 'Payment failure recorded');
        } catch (\Exception $e) {
            Log::error('Payment failure handling error', [
                'error' => $e->getMessage(),
                'order_id' => $request->razorpay_order_id
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
            'data' => $request->all()
        ]);

        try {
            // Verify webhook signature
            $webhookSignature = $request->header('X-Razorpay-Signature');
            $webhookSecret = config('services.razorpay.webhook_secret');

            if ($webhookSecret) {
                $this->razorpay->utility->verifyWebhookSignature(
                    json_encode($request->all()),
                    $webhookSignature,
                    $webhookSecret
                );
            }

            $event = $request->input('event');
            $payload = $request->input('payload');

            // Handle different webhook events
            switch ($event) {
                case 'payment.authorized':
                case 'payment.captured':
                    $this->handlePaymentSuccess($payload);
                    break;

                case 'payment.failed':
                    $this->handlePaymentFailed($payload);
                    break;

                case 'order.paid':
                    $this->handleOrderPaid($payload);
                    break;

                default:
                    Log::info('Unhandled webhook event', ['event' => $event]);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'event' => $request->input('event')
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

            return $this->success([
                'payment' => $payment,
                'appointment' => $payment->appointment,
                'status' => strtoupper($payment->status)
            ]);
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
    private function handlePaymentSuccess(array $payload): void
    {
        $paymentEntity = $payload['payment']['entity'] ?? null;

        if (!$paymentEntity) {
            return;
        }

        $orderId = $paymentEntity['order_id'] ?? null;
        $payment = Payment::where('transaction_id', $orderId)->first();

        if ($payment && $payment->status !== 'paid') {
            $paymentDetails = json_decode($payment->payment_details, true) ?? [];
            $paymentDetails['razorpay_payment_id'] = $paymentEntity['id'];
            $paymentDetails['webhook_received_at'] = now()->toDateTimeString();

            $payment->update([
                'status' => 'paid',
                'payment_details' => json_encode($paymentDetails)
            ]);

            if ($payment->appointment) {
                $payment->appointment->update(['status' => 'confirmed']);
            }

            Log::info('Payment updated via webhook', [
                'payment_id' => $payment->id,
                'razorpay_payment_id' => $paymentEntity['id']
            ]);
        }
    }

    /**
     * Handle failed payment webhook
     */
    private function handlePaymentFailed(array $payload): void
    {
        $paymentEntity = $payload['payment']['entity'] ?? null;

        if (!$paymentEntity) {
            return;
        }

        $orderId = $paymentEntity['order_id'] ?? null;
        $payment = Payment::where('transaction_id', $orderId)->first();

        if ($payment && $payment->status === 'pending') {
            $paymentDetails = json_decode($payment->payment_details, true) ?? [];
            $paymentDetails['error_code'] = $paymentEntity['error_code'] ?? null;
            $paymentDetails['error_description'] = $paymentEntity['error_description'] ?? null;
            $paymentDetails['webhook_received_at'] = now()->toDateTimeString();

            $payment->update([
                'status' => 'failed',
                'payment_details' => json_encode($paymentDetails)
            ]);

            Log::warning('Payment failed via webhook', [
                'payment_id' => $payment->id
            ]);
        }
    }

    /**
     * Handle order paid webhook
     */
    private function handleOrderPaid(array $payload): void
    {
        $orderEntity = $payload['order']['entity'] ?? null;

        if (!$orderEntity) {
            return;
        }

        $orderId = $orderEntity['id'] ?? null;
        $payment = Payment::where('transaction_id', $orderId)->first();

        if ($payment && $payment->status !== 'paid') {
            $payment->update(['status' => 'paid']);

            if ($payment->appointment) {
                $payment->appointment->update(['status' => 'confirmed']);
            }

            Log::info('Order marked as paid via webhook', [
                'payment_id' => $payment->id
            ]);
        }
    }
}
