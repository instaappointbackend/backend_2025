<?php

namespace App\Services\PaymentGateways;

use App\Services\PaymentGateways\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PhonePeService implements PaymentGatewayInterface
{
    private $merchantId;

    private $saltKey;

    private $saltIndex;

    private $baseUrl;

    protected $isProduction;

    public function __construct()
    {
        $this->merchantId = config('services.phonepe.merchant_id');
        $this->saltKey = config('services.phonepe.salt_key');
        $this->saltIndex = config('services.phonepe.salt_index');
        $this->isProduction = config('services.phonepe.production');

        $this->baseUrl = $this->isProduction
            ? 'https://api.phonepe.com/apis/hermes'
            : 'https://api-preprod.phonepe.com/apis/pg-sandbox';

        Log::info('PhonePeService Initialized', [
            'merchant_id' => $this->merchantId,
            'environment' => $this->isProduction ? 'PRODUCTION' : 'SANDBOX',
            'base_url' => $this->baseUrl,
        ]);
    }

    /**
     * Initiate payment with PhonePe
     */
    public function initiatePayment(float $amount, int $userId, array $meta = []): array
    {
        $merchantTransactionId = $this->generateTransactionId();

        Log::info('PhonePe InitiatePayment Started', [
            'transaction_id' => $merchantTransactionId,
            'user_id' => $userId,
            'amount' => $amount,
            'meta' => $meta,
        ]);

        $payload = [
            'merchantId' => $this->merchantId,
            'merchantTransactionId' => $merchantTransactionId,
            'merchantUserId' => 'MUID_' . $userId,
            'amount' => $this->convertToPaise($amount),
            'redirectUrl' => $meta['redirectUrl'] ?? route('phonepe.callback'),
            'redirectMode' => 'POST',
            'callbackUrl' => $meta['callbackUrl'] ?? route('phonepe.callback'),
            'paymentInstrument' => [
                'type' => 'PAY_PAGE',
            ],
        ];

        if (isset($meta['mobileNumber'])) {
            $payload['mobileNumber'] = $this->formatMobileNumber($meta['mobileNumber']);
        }

        $jsonPayload = json_encode($payload);
        $base64Payload = base64_encode($jsonPayload);
        $checksum = $this->generateChecksum($base64Payload, '/pg/v1/pay');

        Log::debug('PhonePe Payload Prepared', [
            'transaction_id' => $merchantTransactionId,
            'payload' => $payload,
            'base64_payload' => $base64Payload,
            'checksum' => $checksum,
        ]);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-VERIFY' => $checksum,
                'accept' => 'application/json',
            ])->post($this->baseUrl . '/pg/v1/pay', [
                'request' => $base64Payload,
            ]);

            $result = $response->json();

            Log::info('PhonePe Initiation Response', [
                'transaction_id' => $merchantTransactionId,
                'http_status' => $response->status(),
                'response' => $result,
            ]);

            if (isset($result['success']) && $result['success'] === true) {
                Log::info('PhonePe Payment Initiation SUCCESS', [
                    'transaction_id' => $merchantTransactionId,
                ]);

                return [
                    'success' => true,
                    'payment_url' => $result['data']['instrumentResponse']['redirectInfo']['url'],
                    'merchant_transaction_id' => $merchantTransactionId,
                    'response_data' => $result['data'],
                ];
            }

            Log::warning('PhonePe Payment Initiation FAILED', [
                'transaction_id' => $merchantTransactionId,
                'response' => $result,
            ]);

            return [
                'success' => false,
                'message' => $result['message'] ?? 'Payment initiation failed',
                'error_code' => $result['code'] ?? null,
                'merchant_transaction_id' => $merchantTransactionId,
            ];
        } catch (\Throwable $e) {
            Log::error('PhonePe Initiation Exception', [
                'transaction_id' => $merchantTransactionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception during payment initiation: ' . $e->getMessage(),
                'merchant_transaction_id' => $merchantTransactionId,
            ];
        }
    }

    /**
     * Check payment status
     */
    public function checkPaymentStatus(string $merchantTransactionId): array
    {
        try {
            $apiPath = '/pg/v1/status/' . $this->merchantId . '/' . $merchantTransactionId;
            $checksum = $this->generateChecksum('', $apiPath);

            Log::info('PhonePe Status Check Started', [
                'transaction_id' => $merchantTransactionId,
                'url' => $this->baseUrl . $apiPath,
                'checksum' => $checksum,
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-VERIFY' => $checksum,
                'X-MERCHANT-ID' => $this->merchantId,
            ])->get($this->baseUrl . $apiPath);

            $responseData = $response->json();

            Log::info('PhonePe Status Response Received', [
                'transaction_id' => $merchantTransactionId,
                'http_status' => $response->status(),
                'response' => $responseData,
            ]);

            if (! $response->successful()) {
                Log::warning('PhonePe Status HTTP Failure', [
                    'transaction_id' => $merchantTransactionId,
                    'http_status' => $response->status(),
                    'response' => $responseData,
                ]);

                return [
                    'success' => false,
                    'transactionId' => $merchantTransactionId,
                    'paymentState' => 'HTTP_ERROR',
                    'message' => 'Failed to connect to PhonePe API',
                    'responseCode' => $response->status(),
                ];
            }

            $data = $responseData['data'] ?? [];
            $code = $responseData['code'] ?? null;

            $paymentState = $data['paymentState'] ?? $data['state']
                ?? match ($code) {
                    'PAYMENT_SUCCESS' => 'COMPLETED',
                    'PAYMENT_ERROR' => 'FAILED',
                    default => 'PENDING',
                };

            Log::info('PhonePe Status Parsed', [
                'transaction_id' => $merchantTransactionId,
                'payment_state' => $paymentState,
                'code' => $code,
                'data' => $data,
            ]);

            return [
                'success' => (bool) ($responseData['success'] ?? false),
                'transactionId' => $merchantTransactionId,
                'paymentState' => $paymentState,
                'amount' => isset($data['amount']) ? $data['amount'] / 100 : 0,
                'providerReferenceId' => $data['providerReferenceId'] ?? $data['transactionId'] ?? null,
                'responseCode' => $code,
                'message' => $responseData['message'] ?? 'Payment status retrieved successfully',
                'responseData' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('PhonePe Status Check Exception', [
                'transaction_id' => $merchantTransactionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'transactionId' => $merchantTransactionId,
                'paymentState' => 'ERROR',
                'message' => 'Error checking payment status: ' . $e->getMessage(),
            ];
        }
    }

    private function generateTransactionId()
    {
        $txn = 'TXN_' . time() . '_' . rand(1000, 9999);

        Log::debug('Generated Transaction ID', [
            'transaction_id' => $txn,
        ]);

        return $txn;
    }

    private function convertToPaise($amount)
    {
        $paise = (int) ($amount * 100);

        Log::debug('Amount Converted to Paise', [
            'amount' => $amount,
            'paise' => $paise,
        ]);

        return $paise;
    }

    private function formatMobileNumber($mobile)
    {
        $original = $mobile;

        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        $formatted = strlen($mobile) > 10 ? substr($mobile, -10) : $mobile;

        Log::debug('Mobile Number Formatted', [
            'original' => $original,
            'formatted' => $formatted,
        ]);

        return $formatted;
    }

    private function generateChecksum($base64Payload, $apiPath)
    {
        $checksumString = $base64Payload . $apiPath . $this->saltKey;
        $checksum = hash('sha256', $checksumString) . '###' . $this->saltIndex;

        Log::debug('Checksum Generated', [
            'api_path' => $apiPath,
            'checksum' => $checksum,
        ]);

        return $checksum;
    }
}
