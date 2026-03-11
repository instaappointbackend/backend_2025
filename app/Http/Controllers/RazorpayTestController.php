<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentGateways\RazorpayService;
use App\Services\Payments\AppointmentPaymentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Test Controller for Razorpay Integration
 * This simulates mobile app behavior for testing purposes
 */
class RazorpayTestController extends Controller
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
     * Show test payment page (simulates mobile app UI)
     */
    public function showTestPage(Request $request)
    {
        $appointmentId = $request->get('appointment_id', 1);

        return view('razorpay-api-test', [
            'appointmentId' => $appointmentId,
            'apiBaseUrl' => url('/api'),
        ]);
    }

    /**
     * Create order and show Razorpay checkout (Web version)
     */
    public function createOrderAndPay(Request $request)
    {
        try {
            $appointmentId = $request->appointment_id;

            // Check if appointment already paid
            if (Payment::where('appointment_id', $appointmentId)
                ->where('status', 'paid')
                ->exists()
            ) {
                return view('payment.error', [
                    'message' => 'Appointment already booked',
                    'returnUrl' => route('razorpay.test.page'),
                ]);
            }

            // Create payment record
            $payment = $this->paymentService->createPayment(
                $appointmentId,
                ['payment_method' => 'razorpay']
            );

            $userId = $payment->appointment->user_id ?? 1;

            // Create Razorpay order
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
                return view('payment.error', [
                    'message' => $orderResult['message'],
                    'returnUrl' => route('razorpay.test.page'),
                ]);
            }

            // Update payment with order ID
            $payment->update([
                'transaction_id' => $orderResult['order_id'],
                'payment_method' => 'razorpay',
                'payment_details' => json_encode([
                    'razorpay_order_id' => $orderResult['order_id'],
                    'created_at' => now()->toDateTimeString(),
                ]),
            ]);

            // Show Razorpay checkout page
            return view('razorpay-checkout', [
                'order_id' => $orderResult['order_id'],
                'amount' => $orderResult['amount'],
                'currency' => $orderResult['currency'],
                'key_id' => $orderResult['key_id'],
                'payment_id' => $payment->id,
                'appointment' => $payment->appointment,
                // 'callbackUrl' => route('razorpay.test.callback')
                'callbackUrl' => route('razorpay.test.api-tester'),
            ]);
        } catch (\Exception $e) {
            Log::error('Test payment creation failed', [
                'error' => $e->getMessage(),
                'appointment_id' => $request->appointment_id,
            ]);

            return view('payment.error', [
                'message' => 'Failed to create payment: ' . $e->getMessage(),
                'returnUrl' => route('razorpay.test.page'),
            ]);
        }
    }

    /**
     * Handle callback after payment (simulates mobile app callback)
     */
    public function handleCallback(Request $request)
    {
        try {
            $razorpayPaymentId = $request->razorpay_payment_id;
            $razorpayOrderId = $request->razorpay_order_id;
            $razorpaySignature = $request->razorpay_signature;

            // Find payment
            $payment = Payment::where('transaction_id', $razorpayOrderId)->first();

            if (! $payment) {
                return view('payment.error', [
                    'message' => 'Payment not found',
                    'returnUrl' => route('razorpay.test.page'),
                ]);
            }

            // Verify signature
            $verificationResult = $this->razorpayService->verifyPaymentSignature(
                $razorpayOrderId,
                $razorpayPaymentId,
                $razorpaySignature
            );

            if (! $verificationResult['success'] || ! $verificationResult['verified']) {
                $payment->update(['status' => 'failed']);

                return view('payment.bridge-failed', [
                    'payment' => $payment,
                    'errorMessage' => 'Payment verification failed',
                    'returnUrl' => route('razorpay.test.page'),
                ]);
            }

            // Get payment details
            $paymentDetailsResult = $this->razorpayService->getPaymentDetails($razorpayPaymentId);

            // Update payment
            $paymentDetails = json_decode($payment->payment_details, true) ?? [];
            $paymentDetails['razorpay_payment_id'] = $razorpayPaymentId;
            $paymentDetails['razorpay_signature'] = $razorpaySignature;
            $paymentDetails['verified_at'] = now()->toDateTimeString();

            if ($paymentDetailsResult['success']) {
                $paymentDetails['payment_method'] = $paymentDetailsResult['payment']['method'] ?? null;
            }

            $payment->update([
                'status' => 'paid',
                'payment_details' => json_encode($paymentDetails),
            ]);



            // Update appointment
            if ($payment->appointment) {
                $payment->appointment->update(['status' => 'confirmed']);
            }

            return view('payment.bridge-success', [
                'payment' => $payment,
                'appointment' => $payment->appointment,
                'returnUrl' => route('razorpay.test.page'),
            ]);
        } catch (\Exception $e) {
            Log::error('Test callback error', [
                'error' => $e->getMessage(),
            ]);

            return view('payment.error', [
                'message' => 'Payment processing failed',
                'returnUrl' => route('razorpay.test.page'),
            ]);
        }
    }

    /**
     * Test API endpoints page
     */
    public function showApiTestPage(Request $request)
    {
        $razorpayPaymentId = $request->razorpay_payment_id ?? $request->query('razorpay_payment_id');
        $razorpayOrderId = $request->razorpay_order_id ?? $request->query('razorpay_order_id');
        $razorpaySignature = $request->razorpay_signature ?? $request->query('razorpay_signature');

        // dd($razorpaySignature);
        return view('razorpay-api-test', compact('razorpayPaymentId', 'razorpayOrderId', 'razorpaySignature'));
    }
}
