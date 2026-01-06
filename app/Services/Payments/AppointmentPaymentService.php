<?php

namespace App\Services\Payments;

use App\Models\Appointment;
use App\Models\AppointmentSettings;
use App\Models\Payment;
use App\Services\NotificationService;
use App\Services\PaymentGateways\Contracts\PaymentGatewayInterface;
use App\Services\PaymentGateways\PaymentGatewayFactory;
use App\Services\TimeSlotBlockingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentPaymentService
{
    private PaymentGatewayInterface $paymentGateway;
    private NotificationService $notificationService;
    private TimeSlotBlockingService $timeSlotService;

    public function __construct(
        NotificationService $notificationService,
        TimeSlotBlockingService $timeSlotService,
        PaymentGatewayInterface $paymentGateway
    ) {
        $this->paymentGateway = $paymentGateway;
        $this->notificationService = $notificationService;
        $this->timeSlotService = $timeSlotService;
    }

    /**
     * Create payment record for appointment
     */
    public function createPayment(int $appointmentId, array $metadata = []): Payment
    {
        $appointment = Appointment::with(['client', 'service', 'provider'])->findOrFail($appointmentId);

        if (!$appointment->user_id) {
            throw new \Exception('Invalid appointment configuration: provider not specified');
        }

        // Check if payment already exists
        $existingPayment = Payment::where('appointment_id', $appointmentId)
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PAID])
            ->first();

        if ($existingPayment) {
            return $existingPayment;
        }

        $payment = new Payment([
            'appointment_id' => $appointment->id,
            'user_id' => $appointment->client_id,
            'provider_id' => $appointment->user_id,
            'transaction_id' => $this->generateTransactionId(),
            'payment_method' => $this->getPaymentMethodName(),
            'payment_mode' => 'online',
            //'amount' => 1, // Test amount
            'amount' => $appointment->service->price,
            'currency' => 'INR',
            'status' => Payment::STATUS_PENDING,
            'booking_price' => $appointment->booking_price,
            'platform_fee' => $appointment->platform_fees ?? 0,
            'other_charges' => $appointment->other_charges ?? 0,
            'gst_amount' => $appointment->gst ?? 0,
            'discount_amount' => $appointment->discount_amount ?? 0,
            'discount_percentage' => $appointment->discount_percentage ?? 0,
            'home_visit_fee' => $appointment->home_visit_fee ?? 0,
            'additional_services_fee' => $appointment->additional_services_fee ?? 0,
            'net_amount' => $appointment->payment_amount ?? $appointment->final_price ?? 0.1,
            'payment_details' => json_encode(array_merge([
                'initiated_at' => now()->toIso8601String()
            ], $metadata))
        ]);

        $payment->save();

        return $payment;
    }

    /**
     * Update payment metadata
     */
    public function updatePaymentMetadata(Payment $payment, array $metadata): void
    {
        $paymentDetails = json_decode($payment->payment_details, true) ?? [];
        $paymentDetails = array_merge($paymentDetails, $metadata);

        $payment->update([
            'payment_details' => json_encode($paymentDetails)
        ]);
    }

    /**
     * Initiate payment via gateway
     */
    public function initiatePayment(Payment $payment, array $options = []): array
    {
        $appointment = $payment->appointment;

        // Prepare gateway options
        $gatewayOptions = array_merge([
            'redirectUrl' => $options['redirectUrl'] ?? url('/'),
            'callbackUrl' => $options['callbackUrl'] ?? url('/'),
            'mobileNumber' => $this->formatMobileNumber($appointment->client->phone ?? '1234567890')
        ], $options);

        // Initiate payment through gateway
        $paymentResponse = $this->paymentGateway->initiatePayment(
            $payment->amount,
            $appointment->client_id,
            $gatewayOptions
        );

        if (!$paymentResponse['success']) {
            throw new \Exception($paymentResponse['message'] ?? 'Failed to initialize payment');
        }

        // Update payment with gateway response
        $this->updatePaymentMetadata($payment, [
            'gateway_initiate_response' => $paymentResponse['response_data'] ?? $paymentResponse
        ]);

        $payment->update([
            'transaction_id' => $paymentResponse['merchant_transaction_id']
        ]);

        return $paymentResponse;
    }

    /**
     * Process payment callback (Gateway agnostic)
     */
    public function processCallback(string $transactionId): array
    {
        DB::beginTransaction();

        try {
            $payment = Payment::with([
                'appointment.client',
                'appointment.user',
                'appointment.service',
                'appointment.comboService'
            ])->where('transaction_id', $transactionId)->first();

            if (!$payment || !$payment->appointment) {
                throw new \Exception('Payment or appointment not found');
            }

            $appointment = $payment->appointment;


            // Check payment status from gateway
            $status = $this->paymentGateway->checkPaymentStatus($transactionId);

            Log::info('Processing payment callback', [
                'transaction_id' => $transactionId,
                'payment_state' => $status['paymentState'] ?? 'UNKNOWN',
                'appointment_id' => $appointment->id,
                'gateway' => $payment->payment_method
            ]);

            // Process based on payment state
            if (isset($status['paymentState']) && $status['paymentState'] === 'COMPLETED') {
                $this->handleSuccessfulPayment($payment, $appointment, $status);

                DB::commit();

                return [
                    'success' => true,
                    'status' => 'COMPLETED',
                    'payment' => $payment->fresh(),
                    'appointment' => $appointment->fresh()
                ];
            } else {
                $this->handleFailedPayment($payment, $appointment, $status);

                DB::commit();

                return [
                    'success' => false,
                    'status' => 'FAILED',
                    'payment' => $payment->fresh(),
                    'message' => $status['message'] ?? 'Payment not successful'
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment callback processing failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Process webhook (Gateway agnostic)
     */
    public function processWebhook(array $webhookData, string $gateway = null): array
    {
        try {


            // Extract transaction ID based on gateway
            $transactionId = $this->extractTransactionIdFromWebhook($webhookData);

            if (!$transactionId) {
                throw new \Exception('Missing transaction ID in webhook');
            }

            $payment = Payment::where('transaction_id', $transactionId)->first();

            if (!$payment) {
                throw new \Exception('Payment not found');
            }


            // Check payment status
            $status = $this->paymentGateway->checkPaymentStatus($transactionId);

            // Update payment with webhook data
            $this->updatePaymentMetadata($payment, [
                'webhook_data' => $webhookData,
                'webhook_status' => $status,
                'webhook_received_at' => now()->toIso8601String()
            ]);

            // Determine new status
            $newStatus = $this->mapGatewayStatusToPaymentStatus($status['paymentState'] ?? 'PENDING');

            $payment->update(['status' => $newStatus]);

            // Update appointment payment status
            if ($payment->appointment) {
                $payment->appointment->update(['payment_status' => $newStatus]);
            }

            return [
                'success' => true,
                'payment' => $payment->fresh()
            ];
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'webhook_data' => $webhookData,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Handle successful payment
     */
    private function handleSuccessfulPayment(Payment $payment, Appointment $appointment, array $status): void
    {
        $this->updatePaymentMetadata($payment, [
            'gateway_response' => $status,
            'confirmed_at' => now()->toIso8601String()
        ]);

        // Update payment
        $payment->update(['status' => Payment::STATUS_PAID]);

        // Update appointment
        $settings = AppointmentSettings::where('user_id', $appointment->user_id)->first();
        $newStatus = ($settings && $settings->auto_confirm_appointments)
            ? Appointment::STATUS_CONFIRMED
            : Appointment::STATUS_PENDING;

        $appointment->update([
            'status' => $newStatus,
            'payment_status' => Payment::STATUS_PAID
        ]);

        // Confirm time slot
        $this->timeSlotService->confirmTimeSlotBooking($appointment->id);

        // Send notifications
        $this->sendPaymentSuccessNotifications($appointment);
    }

    /**
     * Handle failed payment
     */
    private function handleFailedPayment(Payment $payment, Appointment $appointment, array $status): void
    {
        $this->updatePaymentMetadata($payment, [
            'gateway_response' => $status,
            'failed_at' => now()->toIso8601String()
        ]);

        // Update payment
        $payment->update(['status' => Payment::STATUS_FAILED]);

        // Release time slots
        $this->timeSlotService->releaseTimeSlotsForAppointment($appointment->id);

        // Delete draft appointment
        $appointment->delete();
    }

    /**
     * Send notifications after successful payment
     */
    private function sendPaymentSuccessNotifications(Appointment $appointment): void
    {
        $serviceName = $appointment->service->name
            ?? $appointment->comboService->name
            ?? 'Service';

        $appointmentDate = Carbon::parse($appointment->date)->format('D, M d, Y');
        $appointmentTime = Carbon::parse($appointment->start_time)->format('h:i A');

        // Notify provider
        $this->notificationService->sendPushNotification(
            $appointment->user_id,
            'New Paid Appointment',
            "New paid appointment from {$appointment->client->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime}.",
            [
                'type' => 'appointment_created_paid',
                'appointment_id' => $appointment->id,
                'appointment_date' => $appointment->date,
                'appointment_time' => $appointment->start_time,
                'client_id' => $appointment->client_id,
                'client_name' => $appointment->client->name,
                'service_name' => $serviceName,
                'payment_status' => 'paid',
                'amount' => $appointment->payment_amount
            ]
        );

        // Notify client
        $this->notificationService->sendPushNotification(
            $appointment->client_id,
            'Payment Successful - Appointment Confirmed',
            "Your payment was successful! Your appointment with {$appointment->user->name} for {$serviceName} is confirmed for {$appointmentDate} at {$appointmentTime}.",
            [
                'type' => 'appointment_confirmed_after_payment',
                'appointment_id' => $appointment->id,
                'appointment_date' => $appointment->date,
                'appointment_time' => $appointment->start_time,
                'provider_id' => $appointment->user_id,
                'provider_name' => $appointment->user->name,
                'service_name' => $serviceName,
                'payment_status' => 'paid',
                'amount' => $appointment->payment_amount
            ]
        );
    }

    /**
     * Generate transaction ID
     */
    private function generateTransactionId(): string
    {
        return 'TXN_' . time() . '_' . rand(1000, 9999);
    }

    /**
     * Format mobile number
     */
    private function formatMobileNumber(string $mobile): string
    {
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        return strlen($mobile) > 10 ? substr($mobile, -10) : $mobile;
    }

    /**
     * Get payment method name from current gateway
     */
    private function getPaymentMethodName(): string
    {
        $className = get_class($this->paymentGateway);

        return match (true) {
            str_contains($className, 'PhonePe') => 'phonepe',
            str_contains($className, 'Razorpay') => 'razorpay',
            default => 'unknown'
        };
    }

    /**
     * Set gateway based on payment method
     */
    private function setGatewayFromPayment(Payment $payment): void
    {
        // if ($payment->payment_method) {
        //     $this->paymentGateway = PaymentGatewayFactory::create($payment->payment_method);
        // }
    }

    /**
     * Extract transaction ID from webhook data
     */
    private function extractTransactionIdFromWebhook(array $webhookData): ?string
    {
        // PhonePe format
        if (isset($webhookData['merchantTransactionId'])) {
            return $webhookData['merchantTransactionId'];
        }

        // Razorpay format
        if (isset($webhookData['payload']['payment']['entity']['order_id'])) {
            return $webhookData['payload']['payment']['entity']['order_id'];
        }

        return null;
    }

    /**
     * Map gateway status to payment status
     */
    private function mapGatewayStatusToPaymentStatus(string $gatewayStatus): string
    {
        return match ($gatewayStatus) {
            'COMPLETED' => Payment::STATUS_PAID,
            'FAILED' => Payment::STATUS_FAILED,
            default => Payment::STATUS_PENDING
        };
    }
}
