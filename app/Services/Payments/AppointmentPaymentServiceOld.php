<?php

namespace App\Services\Payments;

use App\Models\Appointment;
use App\Models\AppointmentSettings;
use App\Models\Payment;
use App\Services\NotificationService;
use App\Services\PaymentGateways\PhonePeService;
use App\Services\TimeSlotBlockingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentPaymentService
{
    private $phonePeService;

    private $notificationService;

    private $timeSlotService;

    public function __construct(
        PhonePeService $phonePeService,
        NotificationService $notificationService,
        TimeSlotBlockingService $timeSlotService
    ) {
        $this->phonePeService = $phonePeService;
        $this->notificationService = $notificationService;
        $this->timeSlotService = $timeSlotService;
    }

    /**
     * Create or update payment record for appointment
     */
    public function createOrUpdatePayment($appointmentId, $appReturnUrl)
    {
        $appointment = Appointment::with(['client', 'service', 'provider'])->find($appointmentId);

        if (! $appointment) {
            throw new \Exception('Appointment not found');
        }

        if (! $appointment->user_id) {
            throw new \Exception('Invalid appointment configuration: provider not specified');
        }

        $payment = Payment::where('appointment_id', $appointmentId)->first();

        if (! $payment) {
            $payment = $this->createPaymentRecord($appointment, $appReturnUrl);
        } else {
            $this->updatePaymentRecord($payment, $appointment, $appReturnUrl);
        }

        return $payment;
    }

    /**
     * Initiate PhonePe payment for appointment
     */
    public function initiatePayment($payment)
    {
        $appointment = $payment->appointment;

        $mobileNumber = $this->formatMobileNumber($appointment->client->phone ?? '1234567890');
        $callbackUrl = route('phonepe.bridge.callback');
        $webhookUrl = route('phonepe.bridge.webhook', [], true);

        $paymentResponse = $this->phonePeService->initiatePayment(
            $payment->amount,
            $appointment->client_id,
            [
                'redirectUrl' => $callbackUrl,
                'callbackUrl' => $webhookUrl,
                'mobileNumber' => $mobileNumber,
            ]
        );

        if (! $paymentResponse['success']) {
            throw new \Exception($paymentResponse['message'] ?? 'Failed to initialize payment');
        }

        // Update payment with PhonePe response
        $paymentDetails = json_decode($payment->payment_details, true) ?? [];
        $paymentDetails['phonepe_initiate_response'] = $paymentResponse['response_data'];

        $payment->update([
            'transaction_id' => $paymentResponse['merchant_transaction_id'],
            'payment_details' => json_encode($paymentDetails),
        ]);

        return $paymentResponse;
    }

    /**
     * Process payment callback
     */
    public function processCallback($transactionId)
    {
        DB::beginTransaction();

        try {
            $payment = Payment::with('appointment.client', 'appointment.user', 'appointment.service', 'appointment.comboService')
                ->where('transaction_id', $transactionId)
                ->first();

            if (! $payment || ! $payment->appointment) {
                throw new \Exception('Payment or appointment not found');
            }

            $appointment = $payment->appointment;
            $status = $this->phonePeService->checkPaymentStatus($transactionId);

            Log::info('Processing payment callback', [
                'transaction_id' => $transactionId,
                'payment_state' => $status['paymentState'] ?? 'UNKNOWN',
                'appointment_id' => $appointment->id,
            ]);

            if (isset($status['paymentState']) && $status['paymentState'] === 'COMPLETED') {
                $this->handleSuccessfulPayment($payment, $appointment, $status);

                DB::commit();

                return [
                    'success' => true,
                    'status' => 'COMPLETED',
                    'payment' => $payment,
                    'appointment' => $appointment,
                ];
            } else {
                $this->handleFailedPayment($payment, $appointment, $status);

                DB::commit();

                return [
                    'success' => false,
                    'status' => 'FAILED',
                    'payment' => $payment,
                    'message' => $status['message'] ?? 'Payment not successful',
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment callback processing failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle successful payment
     */
    private function handleSuccessfulPayment($payment, $appointment, $status)
    {
        $paymentDetails = json_decode($payment->payment_details, true) ?? [];
        $paymentDetails['phonepe_response'] = $status;
        $paymentDetails['confirmed_at'] = now()->toIso8601String();

        // Update payment
        $payment->update([
            'status' => Payment::STATUS_PAID,
            'payment_details' => json_encode($paymentDetails),
        ]);

        // Update appointment
        $settings = AppointmentSettings::where('user_id', $appointment->user_id)->first();
        $newStatus = ($settings && $settings->auto_confirm_appointments)
            ? Appointment::STATUS_CONFIRMED
            : Appointment::STATUS_PENDING;

        $appointment->update([
            'status' => $newStatus,
            'payment_status' => Payment::STATUS_PAID,
        ]);

        // Confirm time slot
        $this->timeSlotService->confirmTimeSlotBooking($appointment->id);

        // Send notifications
        $this->sendPaymentSuccessNotifications($appointment);
    }

    /**
     * Handle failed payment
     */
    private function handleFailedPayment($payment, $appointment, $status)
    {
        $paymentDetails = json_decode($payment->payment_details, true) ?? [];
        $paymentDetails['phonepe_response'] = $status;
        $paymentDetails['failed_at'] = now()->toIso8601String();

        // Update payment
        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'payment_details' => json_encode($paymentDetails),
        ]);

        // Release time slots
        $this->timeSlotService->releaseTimeSlotsForAppointment($appointment->id);

        // Delete draft appointment
        $appointment->delete();
    }

    /**
     * Send notifications after successful payment
     */
    private function sendPaymentSuccessNotifications($appointment)
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
                'amount' => $appointment->payment_amount,
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
                'amount' => $appointment->payment_amount,
            ]
        );
    }

    /**
     * Create payment record
     */
    private function createPaymentRecord($appointment, $appReturnUrl)
    {
        $payment = new Payment([
            'appointment_id' => $appointment->id,
            'user_id' => $appointment->client_id,
            'provider_id' => $appointment->user_id,
            'transaction_id' => 'TXN_'.time().'_'.rand(1000, 9999),
            'payment_method' => 'phonepe',
            'payment_mode' => 'online',
            'amount' => 1, // Test amount
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
            'payment_details' => json_encode([
                'app_return_url' => $appReturnUrl,
                'initiated_at' => now()->toIso8601String(),
            ]),
        ]);

        $payment->save();

        return $payment;
    }

    /**
     * Update payment record
     */
    private function updatePaymentRecord($payment, $appointment, $appReturnUrl)
    {
        $paymentDetails = json_decode($payment->payment_details, true) ?? [];
        $paymentDetails['app_return_url'] = $appReturnUrl;
        $paymentDetails['initiated_at'] = now()->toIso8601String();

        $payment->update([
            'payment_method' => 'phonepe',
            'payment_mode' => 'online',
            'status' => Payment::STATUS_PENDING,
            'provider_id' => $appointment->user_id,
            'payment_details' => json_encode($paymentDetails),
        ]);
    }

    /**
     * Format mobile number
     */
    private function formatMobileNumber($mobile)
    {
        $mobile = preg_replace('/[^0-9]/', '', $mobile);

        return strlen($mobile) > 10 ? substr($mobile, -10) : $mobile;
    }

    /**
     * Build app return URL
     */
    public function buildAppReturnUrl($baseUrl, $payment, $paymentState)
    {
        $isExpoDevUrl = strpos($baseUrl, 'exp://') === 0 || strpos($baseUrl, 'expo-dev') === 0;

        if ($isExpoDevUrl && strpos($baseUrl, 'exp://') !== 0) {
            $devServer = session('dev_server', '127.0.0.1:8081');
            $baseUrl = "exp://{$devServer}/--/payment/callback";
        }

        $separator = (strpos($baseUrl, '?') !== false) ? '&' : '?';

        $params = [
            'status' => strtolower($payment->status),
            'transaction_id' => $payment->transaction_id,
            'appointment_id' => $payment->appointment_id,
            'amount' => $payment->amount,
            'payment_state' => $paymentState,
        ];

        $queryString = array_map(
            fn ($key, $value) => $key.'='.urlencode($value),
            array_keys($params),
            $params
        );

        return $baseUrl.$separator.implode('&', $queryString);
    }
}
