<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PhonePeController extends Controller
{
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

    // Show payment form
    public function showPaymentForm()
    {
        return view('phonepe.form');
    }

    // Process payment request
    public function processPayment(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'phone' => 'required|string|min:10|max:12',
            'amount' => 'required|numeric|min:1',
        ]);

        try {
            // Create a unique transaction ID
            $transactionId = 'ORDER_'.time().'_'.rand(100, 999);

            // Format mobile number - ensure it's 10 digits
            $mobileNumber = $request->phone;
            if (strlen($mobileNumber) > 10) {
                $mobileNumber = substr($mobileNumber, -10);
            }

            // Convert amount to paise (PhonePe requires amount in paise)
            $amountInPaise = (int) ($request->amount * 100);

            // Create the payload
            $payload = [
                'merchantId' => $this->merchantId,
                'merchantTransactionId' => $transactionId,
                'merchantUserId' => 'MUID_'.time(),
                'amount' => $amountInPaise,
                'redirectUrl' => route('phonepe.callback'),
                'redirectMode' => 'POST',
                'callbackUrl' => route('phonepe.webhook'),
                'mobileNumber' => $mobileNumber,
                'paymentInstrument' => [
                    'type' => 'PAY_PAGE',
                ],
            ];

            // Store transaction info in session
            session([
                'payment_transaction_id' => $transactionId,
                'payment_amount' => $request->amount,
                'payment_name' => $request->name,
                'payment_email' => $request->email,
                'payment_phone' => $request->phone,
            ]);

            // Convert payload to JSON
            $payloadJson = json_encode($payload);

            // Base64 encode the payload
            $payloadBase64 = base64_encode($payloadJson);

            // Generate checksum
            $checksumPath = '/pg/v1/pay';
            $string = $payloadBase64.$checksumPath.$this->saltKey;
            $checksum = hash('sha256', $string).'###'.$this->saltIndex;

            // Prepare final API request URL
            $requestUrl = $this->baseUrl.'/pg/v1/pay';

            // Log the request for debugging
            Log::info('PhonePe payment initiation', [
                'url' => $requestUrl,
                'transaction_id' => $transactionId,
                'amount' => $request->amount,
                'payload' => $payload,
            ]);

            // Make the API call to PhonePe
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-VERIFY' => $checksum,
            ])->post($requestUrl, [
                'request' => $payloadBase64,
            ]);

            // Log the response
            Log::info('PhonePe initiate response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            // Parse the response
            $responseData = $response->json();

            // Check if the request was successful
            if ($response->successful() && isset($responseData['success']) && $responseData['success'] === true) {
                // Get the redirect URL for payment
                $redirectUrl = $responseData['data']['instrumentResponse']['redirectInfo']['url'];

                // Save transaction in database (optional, implement if needed)
                // $this->saveTransaction($payload, $responseData);

                // Redirect user to PhonePe payment page
                return redirect()->away($redirectUrl);
            } else {
                // If payment initiation failed
                Log::error('PhonePe payment initiation failed', [
                    'error' => $responseData,
                ]);

                return redirect()->route('phonepe.form')
                    ->with('error', 'Failed to initialize payment: '.($responseData['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('Exception in PhonePe payment process', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('phonepe.form')
                ->with('error', 'An error occurred: '.$e->getMessage());
        }
    }

    // Handle the callback from PhonePe
    public function handleCallback(Request $request)
    {
        Log::info('PhonePe callback received', [
            'data' => $request->all(),
            'headers' => $request->header(),
        ]);

        // Get the transaction ID from the callback
        $transactionId = $request->input('transactionId', session('payment_transaction_id'));

        if (! $transactionId) {
            return view('phonepe.failed', [
                'message' => 'Invalid transaction ID',
            ]);
        }

        try {
            // Check payment status
            $status = $this->checkPaymentStatus($transactionId);

            // If payment was successful
            if ($status['success'] && $status['paymentState'] === 'COMPLETED') {
                // Get payment details from session
                $paymentDetails = [
                    'transaction_id' => $transactionId,
                    'amount' => session('payment_amount'),
                    'name' => session('payment_name'),
                    'email' => session('payment_email'),
                    'phone' => session('payment_phone'),
                ];

                // Here you would typically save the completed payment to your database
                // $this->saveCompletedPayment($paymentDetails, $status);

                return view('phonepe.success', [
                    'transaction' => $paymentDetails,
                    'payment' => $status,
                ]);
            } else {
                // Payment failed or is pending
                return view('phonepe.failed', [
                    'message' => $status['message'] ?? 'Payment was not successful',
                    'status' => $status,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing PhonePe callback', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('phonepe.failed', [
                'message' => 'An error occurred while processing your payment',
            ]);
        }
    }

    // Handle webhook from PhonePe (silent notification)
    public function handleWebhook(Request $request)
    {
        Log::info('PhonePe webhook received', [
            'data' => $request->all(),
            'headers' => $request->header(),
        ]);

        try {
            // Get transaction ID from webhook data
            $webhookData = $request->all();
            $transactionId = $webhookData['merchantTransactionId'] ?? null;

            if (! $transactionId) {
                return response()->json([
                    'status' => 'FAILURE',
                    'message' => 'Missing transaction ID',
                ], 400);
            }

            // Process the webhook silently (no user interaction)
            // Here you would update your database with the payment status
            // $this->updatePaymentStatusFromWebhook($transactionId, $webhookData);

            // Return success response to PhonePe
            return response()->json([
                'status' => 'SUCCESS',
                'message' => 'Webhook processed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing PhonePe webhook', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'FAILURE',
                'message' => 'Webhook processing failed: '.$e->getMessage(),
            ], 500);
        }
    }

    // Check payment status with PhonePe
    private function checkPaymentStatus($transactionId)
    {
        try {
            // Construct the API URL
            $apiUrl = $this->baseUrl.'/pg/v1/status/'.$this->merchantId.'/'.$transactionId;

            // Generate checksum
            $string = '/pg/v1/status/'.$this->merchantId.'/'.$transactionId.$this->saltKey;
            $checksum = hash('sha256', $string).'###'.$this->saltIndex;

            // Log the request
            Log::info('PhonePe status check request', [
                'url' => $apiUrl,
                'transaction_id' => $transactionId,
            ]);

            // Make the API call to PhonePe
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-VERIFY' => $checksum,
                'X-MERCHANT-ID' => $this->merchantId,
            ])->get($apiUrl);

            // Log the response
            Log::info('PhonePe status check response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            // Parse the response
            $responseData = $response->json();

            // Check if the request was successful
            if ($response->successful() && isset($responseData['success']) && $responseData['success'] === true) {
                // Determine payment state
                $paymentState = 'PENDING';

                if (isset($responseData['data']['paymentState'])) {
                    $paymentState = $responseData['data']['paymentState'];
                } elseif (isset($responseData['code'])) {
                    if ($responseData['code'] === 'PAYMENT_SUCCESS') {
                        $paymentState = 'COMPLETED';
                    } elseif ($responseData['code'] === 'PAYMENT_ERROR') {
                        $paymentState = 'FAILED';
                    }
                }

                return [
                    'success' => true,
                    'transactionId' => $transactionId,
                    'paymentState' => $paymentState,
                    'amount' => isset($responseData['data']['amount']) ? $responseData['data']['amount'] / 100 : 0,
                    'message' => 'Payment status retrieved successfully',
                    'providerReferenceId' => $responseData['data']['providerReferenceId'] ?? null,
                    'responseCode' => $responseData['code'] ?? null,
                    'responseData' => $responseData['data'] ?? [],
                ];
            } else {
                // Failed to get status
                return [
                    'success' => false,
                    'transactionId' => $transactionId,
                    'paymentState' => 'UNKNOWN',
                    'message' => $responseData['message'] ?? 'Failed to check payment status',
                    'responseCode' => $responseData['code'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception in PhonePe status check', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'transactionId' => $transactionId,
                'paymentState' => 'ERROR',
                'message' => 'Error checking payment status: '.$e->getMessage(),
            ];
        }
    }
}
