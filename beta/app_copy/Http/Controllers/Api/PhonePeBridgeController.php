<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppointmentSettings;
use App\Services\NotificationService;
use App\Services\TimeSlotBlockingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Appointment;
use App\Models\Payment;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Validator;

class PhonePeBridgeController extends Controller
{
    use ApiResponseTrait;

    protected $merchantId;
    protected $saltKey;
    protected $saltIndex;
    protected $isProduction;
    protected $baseUrl;

    public function __construct()
    {
        $this->merchantId = config('services.phonepe.merchant_id');
        $this->saltKey = config('services.phonepe.salt_key');
        $this->saltIndex = config('services.phonepe.salt_index');
        $this->isProduction = config('services.phonepe.production');

        if ($this->isProduction) {
            $this->baseUrl = 'https://api.phonepe.com/apis/hermes';
        } else {
            $this->baseUrl = 'https://api-preprod.phonepe.com/apis/pg-sandbox';
        }
    }

    /**
     * Show PhonePe payment page in WebView
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function showPaymentPage(Request $request)
    {

        // Validate input
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|integer|exists:appointments,id',
            'app_return_url' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->view('payment.error', [
                'message' => 'Invalid request. ' . $validator->errors()->first()
            ]);
        }

        // Get appointment details
        $appointmentId = $request->appointment_id;
        $appReturnUrl = $request->app_return_url;

        // Check if appointment exists
        $appointment = Appointment::with(['client', 'service', 'provider'])->find($appointmentId);

        if (!$appointment) {
            return response()->view('payment.error', [
                'message' => 'Appointment not found.'
            ]);
        }

        // Make sure that provider_id is not null
        if (!$appointment->user_id) {
            Log::error("PhonePeBridge error: provider_id (user_id) is null for appointment {$appointmentId}");
            return response()->view('payment.error', [
                'message' => 'Invalid appointment configuration: provider not specified.'
            ]);
        }

        // Check if payment exists for this appointment
        $payment = Payment::where('appointment_id', $appointmentId)->first();
//print_r($payment);die;
        if (!$payment) {
            // Create a new payment record if it doesn't exist
            try {
                $payment = new Payment([
                    'appointment_id' => $appointmentId,
                    'user_id' => $appointment->client_id,
                    'provider_id' => $appointment->user_id, // Ensure provider_id is set from appointment
                    'transaction_id' => 'TXN_' . time() . '_' . rand(1000, 9999),
                    'payment_method' => 'phonepe',
                    'payment_mode' => 'online',
//                    'amount' => $appointment->payment_amount ?? $appointment->final_price ?? 1, // Default to 1 if not set
                    'amount' =>  1, // Small test amount (10 paise)
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
                        'initiated_at' => now()->toIso8601String()
                    ])
                ]);
//print_r($payment);die;
                // Log the payment data for debugging
                Log::info("Creating new payment for appointment", [
                    'appointment_id' => $appointmentId,
                    'user_id' => $appointment->client_id,
                    'provider_id' => $appointment->user_id,
                    'amount' => $payment->amount
                ]);

                $payment->save();
            } catch (\Exception $e) {
                Log::error("PhonePeBridge payment creation error: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'appointment' => $appointment->toArray()
                ]);

                return response()->view('payment.error', [
                    'message' => 'Failed to create payment record: ' . $e->getMessage()
                ]);
            }
        } else {
            // Update existing payment record
            try {
                $paymentDetails = json_decode($payment->payment_details, true) ?? [];
                $paymentDetails['app_return_url'] = $appReturnUrl;
                $paymentDetails['initiated_at'] = now()->toIso8601String();

                $payment->update([
                    'payment_method' => 'phonepe',
                    'payment_mode' => 'online',
                    'status' => Payment::STATUS_PENDING,
                    'provider_id' => $appointment->user_id, // Ensure provider_id is updated
                    'payment_details' => json_encode($paymentDetails)
                ]);
            } catch (\Exception $e) {
                Log::error("PhonePeBridge payment update error: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'payment_id' => $payment->id
                ]);

                return response()->view('payment.error', [
                    'message' => 'Failed to update payment record: ' . $e->getMessage()
                ]);
            }
        }

        // Store app return URL in session for later use
        session(['app_return_url' => $appReturnUrl]);
        session(['appointment_id' => $appointmentId]);
        session(['payment_id' => $payment->id]);

        // Render payment page with data
        return view('payment.phonepe-bridge', [
            'appointment' => $appointment,
            'payment' => $payment,
            'appReturnUrl' => $appReturnUrl
        ]);
    }

    /**
     * Process PhonePe payment from WebView
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function processPhonePePayment(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|exists:payments,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), $validator->errors()->first(), 422);
        }

        // Get payment details
        $payment = Payment::with('appointment')->findOrFail($request->payment_id);

        try {
            // Get appointment to pass to PhonePe controller
            $appointment = $payment->appointment;

            // Double-check that we have a valid provider_id
            if (!$appointment->user_id) {
                throw new \Exception('Provider ID is missing from the appointment');
            }

            // Double-check that payment has provider_id
            if (!$payment->provider_id) {
                $payment->provider_id = $appointment->user_id;
                $payment->save();

                Log::info("Updated missing provider_id for payment", [
                    'payment_id' => $payment->id,
                    'provider_id' => $appointment->user_id
                ]);
            }

            // Extract customer information for the payment
            $paymentData = [
                'name' => $appointment->client->name ?? 'Customer',
                'email' => $appointment->client->email ?? 'customer@example.com',
                'phone' => $appointment->client->phone ?? '1234567890',
                'amount' => $payment->amount
            ];

            // Store the payment ID in session
            session(['bridge_payment_id' => $payment->id]);

            // Create a unique transaction ID
            $transactionId = $payment->transaction_id;

            Log::info('PhonePe payment initiation', [
                'transaction_id' => $transactionId,
                'amount' => $payment->amount,
                'payment_id' => $payment->id
            ]);

            // Format mobile number - ensure it's 10 digits
            $mobileNumber = $paymentData['phone'];
            if (strlen($mobileNumber) > 10) {
                $mobileNumber = substr($mobileNumber, -10);
            }

            // Convert amount to paise (PhonePe requires amount in paise)
            $amountInPaise = (int)($payment->amount * 100);

            // Set callback URLs
            $callbackUrl = route('phonepe.bridge.callback');
            $webhookUrl = route('phonepe.bridge.webhook', [], true);

            // Create the payload
            $payload = [
                'merchantId' => $this->merchantId,
                'merchantTransactionId' => $transactionId,
                'merchantUserId' => 'MUID_' . time(),
                'amount' => $amountInPaise,
                'redirectUrl' => $callbackUrl,
                'redirectMode' => 'POST',
                'callbackUrl' => $webhookUrl,
                'mobileNumber' => $mobileNumber,
                'paymentInstrument' => [
                    'type' => 'PAY_PAGE'
                ]
            ];

            // Convert payload to JSON
            $payloadJson = json_encode($payload);

            Log::info('PhonePe payment payload', [
                'payload' => $payload
            ]);

            // Base64 encode the payload
            $payloadBase64 = base64_encode($payloadJson);

            // Generate checksum
            $checksumPath = '/pg/v1/pay';
            $string = $payloadBase64 . $checksumPath . $this->saltKey;
            $checksum = hash('sha256', $string) . "###" . $this->saltIndex;

            // Prepare final API request URL
            $requestUrl = $this->baseUrl . '/pg/v1/pay';

            // Make the API call to PhonePe
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-VERIFY' => $checksum
            ])->post($requestUrl, [
                'request' => $payloadBase64
            ]);

            Log::info('PhonePe initiate response', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            // Parse the response
            $responseData = $response->json();

            // Check if the request was successful
            if ($response->successful() && isset($responseData['success']) && $responseData['success'] === true) {
                // Get the redirect URL for payment
                $redirectUrl = $responseData['data']['instrumentResponse']['redirectInfo']['url'];

                // Update payment record with PhonePe response
                $paymentDetails = json_decode($payment->payment_details, true) ?? [];
                $paymentDetails['phonepe_initiate_response'] = $responseData;
                $payment->payment_details = json_encode($paymentDetails);
                $payment->save();

                // Redirect user to PhonePe payment page
                return redirect()->away($redirectUrl);
            } else {
                // If payment initiation failed
                Log::error('PhonePe payment initiation failed', [
                    'error' => $responseData
                ]);

                return view('payment.error', [
                    'message' => 'Failed to initialize payment: ' . ($responseData['message'] ?? 'Unknown error')
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error in PhonePe bridge payment process', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_id' => $payment->id
            ]);

            return view('payment.error', [
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Handle callback from PhonePe for WebView flow
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function handleBridgeCallback(Request $request)
    {
        $transactionId = $request->input('transactionId', $request->input('merchantTransactionId'));

        Log::info('PhonePe bridge callback received', [
            'data' => $request->all(),
            'transaction' => $transactionId,
        ]);

        try {
            $paymentId = session('bridge_payment_id');

            if (!$paymentId && $transactionId) {
                $payment = Payment::where('transaction_id', $transactionId)->first();
                if ($payment) {
                    $paymentId = $payment->id;
                }
            }

            if (!$paymentId) {
                Log::error('PhonePe bridge callback - No payment ID found');
                return view('payment.bridge-failed', [
                    'returnUrl' => session('app_return_url', 'instaappoint://payment/callback?status=error'),
                    'errorMessage' => 'Payment session expired'
                ]);
            }

            $payment = Payment::with('appointment.client', 'appointment.user', 'appointment.service', 'appointment.comboService')
                ->find($paymentId);

            if (!$payment || !$payment->appointment) {
                Log::error('PhonePe bridge callback - Payment or appointment not found');
                return view('payment.bridge-failed', [
                    'returnUrl' => session('app_return_url', 'instaappoint://payment/callback?status=error'),
                    'errorMessage' => 'Payment record not found'
                ]);
            }

            $appointment = $payment->appointment;
            $paymentDetails = json_decode($payment->payment_details, true) ?? [];
            $appReturnUrl = $paymentDetails['app_return_url'] ?? session('app_return_url');

            // Check payment status with PhonePe
            $status = $this->checkPaymentStatus($payment->transaction_id);

            Log::info('PhonePe payment status', [
                'status' => $status,
                'payment_id' => $payment->id,
                'appointment_id' => $appointment->id
            ]);

            DB::beginTransaction();

            if (isset($status['paymentState']) && $status['paymentState'] === 'COMPLETED') {
                // PAYMENT SUCCESSFUL - Now confirm the appointment

                // Update payment status
                $payment->update([
                    'status' => Payment::STATUS_PAID,
                    'transaction_id' => $transactionId, // Update with actual transaction ID
                    'payment_details' => json_encode(array_merge($paymentDetails, [
                        'phonepe_response' => $status,
                        'confirmed_at' => now()->toIso8601String()
                    ]))
                ]);

                // Update appointment status from payment_pending to pending/confirmed
                $settings = AppointmentSettings::where('user_id', $appointment->user_id)->first();
                $newStatus = ($settings && $settings->auto_confirm_appointments)
                    ? Appointment::STATUS_CONFIRMED
                    : Appointment::STATUS_PENDING;

                $appointment->update([
                    'status' => $newStatus,
                    'payment_status' => Payment::STATUS_PAID
                ]);

                // Confirm time slot booking
                $timeSlotService = new TimeSlotBlockingService();
                $timeSlotService->confirmTimeSlotBooking($appointment->id);

                // NOW send notifications after successful payment
                $this->sendAppointmentNotificationsAfterPayment($appointment);

                DB::commit();

                // Build return URL with success parameters
                $returnUrlWithParams = $this->buildAppReturnUrl($appReturnUrl, $payment, 'COMPLETED');

                Log::info('PhonePe payment successful - Showing success page', [
                    'appointment_id' => $appointment->id,
                    'return_url' => $returnUrlWithParams
                ]);

                return view('payment.bridge-success', [
                    'payment' => $payment,
                    'appointment' => $appointment,
                    'returnUrl' => $returnUrlWithParams
                ]);

            } else {
                // PAYMENT FAILED - Cleanup draft appointment

                // Update payment status
                $payment->update([
                    'status' => Payment::STATUS_FAILED,
                    'payment_details' => json_encode(array_merge($paymentDetails, [
                        'phonepe_response' => $status,
                        'failed_at' => now()->toIso8601String()
                    ]))
                ]);

                // Release time slots
                $timeSlotService = new TimeSlotBlockingService();
                $timeSlotService->releaseTimeSlotsForAppointment($appointment->id);

                // Delete the draft appointment
                $appointment->delete();

                DB::commit();

                // Build return URL with failure parameters
                $returnUrlWithParams = $this->buildAppReturnUrl($appReturnUrl, $payment, 'FAILED');

                Log::info('PhonePe payment failed - Showing failure page', [
                    'payment_status' => $payment->status,
                    'return_url' => $returnUrlWithParams
                ]);

                return view('payment.bridge-failed', [
                    'payment' => $payment,
                    'returnUrl' => $returnUrlWithParams,
                    'status' => $status,
                    'errorMessage' => $status['message'] ?? 'Payment not successful'
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in PhonePe bridge callback', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $appReturnUrl = session('app_return_url', 'instaappoint://payment/callback?status=error');
            return view('payment.bridge-failed', [
                'returnUrl' => $appReturnUrl,
                'errorMessage' => 'Payment processing failed'
            ]);
        }
    }

    /**
     * Send notifications after successful payment
     */
    private function sendAppointmentNotificationsAfterPayment($appointment)
    {
        // Get service name
        $serviceName = '';
        if ($appointment->service) {
            $serviceName = $appointment->service->name;
        } elseif ($appointment->comboService) {
            $serviceName = $appointment->comboService->name;
        }

        // Format dates for notifications
        $appointmentDate = Carbon::parse($appointment->date)->format('D, M d, Y');
        $appointmentTime = Carbon::parse($appointment->start_time)->format('h:i A');

        // Send notification to provider
        $providerTitle = 'New Paid Appointment';
        $providerBody = "New paid appointment from {$appointment->client->name} for {$serviceName} on {$appointmentDate} at {$appointmentTime}.";
        $providerData = [
            'type' => 'appointment_created_paid',
            'appointment_id' => $appointment->id,
            'appointment_date' => $appointment->date,
            'appointment_time' => $appointment->start_time,
            'client_id' => $appointment->client_id,
            'client_name' => $appointment->client->name,
            'service_name' => $serviceName,
            'payment_status' => 'paid',
            'amount' => $appointment->payment_amount
        ];

        app(NotificationService::class)->sendPushNotification(
            $appointment->user_id,
            $providerTitle,
            $providerBody,
            $providerData
        );

        // Send notification to client
        $clientTitle = 'Payment Successful - Appointment Confirmed';
        $clientBody = "Your payment was successful! Your appointment with {$appointment->user->name} for {$serviceName} is confirmed for {$appointmentDate} at {$appointmentTime}.";
        $clientData = [
            'type' => 'appointment_confirmed_after_payment',
            'appointment_id' => $appointment->id,
            'appointment_date' => $appointment->date,
            'appointment_time' => $appointment->start_time,
            'provider_id' => $appointment->user_id,
            'provider_name' => $appointment->user->name,
            'service_name' => $serviceName,
            'payment_status' => 'paid',
            'amount' => $appointment->payment_amount
        ];

        app(NotificationService::class)->sendPushNotification(
            $appointment->client_id,
            $clientTitle,
            $clientBody,
            $clientData
        );
    }

    /**
     * Handle webhook from PhonePe (silent notification)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhook(Request $request)
    {
        Log::info('PhonePe webhook received', [
            'data' => $request->all(),
            'headers' => $request->header()
        ]);

        try {
            // Get transaction ID from webhook data
            $webhookData = $request->all();
            $transactionId = $webhookData['merchantTransactionId'] ?? null;

            if (!$transactionId) {
                return response()->json([
                    'status' => 'FAILURE',
                    'message' => 'Missing transaction ID'
                ], 400);
            }

            // Find payment by transaction ID
            $payment = Payment::where('transaction_id', $transactionId)->first();

            if (!$payment) {
                return response()->json([
                    'status' => 'FAILURE',
                    'message' => 'Payment not found'
                ], 404);
            }

            // Get payment status from PhonePe
            $status = $this->checkPaymentStatus($transactionId);

            // Update payment status
            if ($status['success']) {
                $newStatus = Payment::STATUS_PENDING;

                if ($status['paymentState'] === 'COMPLETED') {
                    $newStatus = Payment::STATUS_PAID;
                } else if ($status['paymentState'] === 'FAILED') {
                    $newStatus = Payment::STATUS_FAILED;
                }

                // Update payment status
                $paymentDetails = json_decode($payment->payment_details, true) ?? [];
                $paymentDetails['phonepe_webhook'] = $webhookData;
                $paymentDetails['phonepe_status'] = $status;
                $paymentDetails['webhook_received_at'] = now()->toIso8601String();

                $payment->update([
                    'status' => $newStatus,
                    'payment_details' => json_encode($paymentDetails)
                ]);

                // Update appointment payment status
                if ($payment->appointment) {
                    $payment->appointment->update([
                        'payment_status' => $newStatus
                    ]);
                }
            }

            // Return success response to PhonePe
            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Webhook processed successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing PhonePe webhook', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'FAILURE',
                'message' => 'Webhook processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check PhonePe payment status
     *
     * @param  string  $transactionId
     * @return array
     */
    private function checkPaymentStatus($transactionId)
    {
        try {
            // Construct the API URL
            $apiUrl = $this->baseUrl . '/pg/v1/status/' . $this->merchantId . '/' . $transactionId;

            // Generate checksum
            $string = '/pg/v1/status/' . $this->merchantId . '/' . $transactionId . $this->saltKey;
            $checksum = hash('sha256', $string) . '###' . $this->saltIndex;

            // Log the request
            Log::info('PhonePe status check request', [
                'url' => $apiUrl,
                'transaction_id' => $transactionId
            ]);

            // Make the API call to PhonePe
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-VERIFY' => $checksum,
                'X-MERCHANT-ID' => $this->merchantId
            ])->get($apiUrl);

            // Log the response
            Log::info('PhonePe status check response', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            // Parse the response
            $responseData = $response->json();

            // Check if the request was successful
            if ($response->successful() && isset($responseData['success']) && $responseData['success'] === true) {
                // Determine payment state
                $paymentState = 'PENDING';

                if (isset($responseData['data']['paymentState']) || isset($responseData['data']['state'])) {
                    $paymentState = $responseData['data']['paymentState'] ?? $responseData['data']['state'];
                } else if (isset($responseData['code'])) {
                    if ($responseData['code'] === 'PAYMENT_SUCCESS') {
                        $paymentState = 'COMPLETED';
                    } else if ($responseData['code'] === 'PAYMENT_ERROR') {
                        $paymentState = 'FAILED';
                    }
                }

                return [
                    'success' => true,
                    'transactionId' => $transactionId,
                    'paymentState' => $paymentState,
                    'amount' => isset($responseData['data']['amount']) ? $responseData['data']['amount'] / 100 : 0,
                    'message' => 'Payment status retrieved successfully',
                    'providerReferenceId' => $responseData['data']['providerReferenceId'] ?? $responseData['data']['transactionId'] ?? null,
                    'responseCode' => $responseData['code'] ?? null,
                    'responseData' => $responseData['data'] ?? []
                ];
            } else {
                // Failed to get status
                return [
                    'success' => false,
                    'transactionId' => $transactionId,
                    'paymentState' => 'UNKNOWN',
                    'message' => $responseData['message'] ?? 'Failed to check payment status',
                    'responseCode' => $responseData['code'] ?? null
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception in PhonePe status check', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'transactionId' => $transactionId,
                'paymentState' => 'ERROR',
                'message' => 'Error checking payment status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Build app return URL with payment status parameters
     * This method handles both standard app scheme URLs and Expo development URLs
     *
     * @param string $baseUrl
     * @param \App\Models\Payment $payment
     * @param string $paymentState
     * @return string
     */
    private function buildAppReturnUrl($baseUrl, $payment, $paymentState)
    {
        // Check if this is an Expo dev URL (either starts with exp:// or expo-dev)
        $isExpoDevUrl = strpos($baseUrl, 'exp://') === 0 || strpos($baseUrl, 'expo-dev') === 0;

        // If it's an Expo development URL that needs construction
        if ($isExpoDevUrl && strpos($baseUrl, 'exp://') !== 0) {
            // Extract dev server details from session if present
            $devServer = session('dev_server', '');

            if (empty($devServer)) {
                Log::warning('No dev server found in session for Expo URL. Using default.');
                $devServer = '127.0.0.1:8081'; // Fallback default
            }

            // Build proper Expo dev URL format
            $baseUrl = "exp://{$devServer}/--/payment/callback";

            Log::info('Constructed Expo dev URL', [
                'dev_server' => $devServer,
                'base_url' => $baseUrl
            ]);
        }

        // Add separator based on whether URL already has query parameters
        $separator = (strpos($baseUrl, '?') !== false) ? '&' : '?';

        // Create query parameters
        $params = [
            'status' => strtolower($payment->status),
            'transaction_id' => $payment->transaction_id,
            'appointment_id' => $payment->appointment_id,
            'amount' => $payment->amount,
            'payment_state' => $paymentState
        ];

        // Build the query string manually to avoid HTML entity encoding
        $queryString = [];
        foreach ($params as $key => $value) {
            $queryString[] = $key . '=' . urlencode($value);
        }

        // Join the parameters and add to the URL
        $url = $baseUrl . $separator . implode('&', $queryString);

        // Log constructed URL for debugging
        Log::info('Built app return URL', [
            'url' => $url
        ]);

        return $url;
    }
}
