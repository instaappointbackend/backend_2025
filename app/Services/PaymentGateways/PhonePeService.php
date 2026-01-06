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
    }

    /**
     * Initiate payment with PhonePe
     */
    public function initiatePayment(float $amount, int $userId, array $meta = []): array
    {
        $merchantTransactionId = $this->generateTransactionId();

        $payload = [
            'merchantId' => $this->merchantId,
            'merchantTransactionId' => $merchantTransactionId,
            'merchantUserId' => 'MUID_' . $userId,
            'amount' => $this->convertToPaise($amount),
            'redirectUrl' => $meta['redirectUrl'] ?? route('phonepe.callback'),
            'redirectMode' => 'POST',
            'callbackUrl' => $meta['callbackUrl'] ?? route('phonepe.callback'),
            'paymentInstrument' => [
                'type' => 'PAY_PAGE'
            ]
        ];

        // Add optional fields if provided
        if (isset($meta['mobileNumber'])) {
            $payload['mobileNumber'] = $this->formatMobileNumber($meta['mobileNumber']);
        }

        $jsonPayload = json_encode($payload);
        $base64Payload = base64_encode($jsonPayload);
        $checksum = $this->generateChecksum($base64Payload, '/pg/v1/pay');

        Log::info('PhonePe Payment Initiation', [
            'transaction_id' => $merchantTransactionId,
            'amount' => $amount,
            'payload' => $payload
        ]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-VERIFY' => $checksum,
            'accept' => 'application/json'
        ])->post($this->baseUrl . '/pg/v1/pay', [
            'request' => $base64Payload
        ]);

        $result = $response->json();

        Log::info('PhonePe Initiation Response', [
            'status' => $response->status(),
            'response' => $result
        ]);

        if (isset($result['success']) && $result['success'] === true) {
            return [
                'success' => true,
                'payment_url' => $result['data']['instrumentResponse']['redirectInfo']['url'],
                'merchant_transaction_id' => $merchantTransactionId,
                'response_data' => $result['data']
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Payment initiation failed',
            'error_code' => $result['code'] ?? null,
            'merchant_transaction_id' => $merchantTransactionId
        ];
    }

    /**
     * Check payment status
     */
    public function checkPaymentStatus(string $merchantTransactionId): array
    {
        try {
            $apiPath = '/pg/v1/status/' . $this->merchantId . '/' . $merchantTransactionId;
            $checksum = $this->generateChecksum('', $apiPath);

            Log::info('PhonePe Status Check', [
                'transaction_id' => $merchantTransactionId,
                'url' => $this->baseUrl . $apiPath
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-VERIFY' => $checksum,
                'X-MERCHANT-ID' => $this->merchantId
            ])->get($this->baseUrl . $apiPath);

            $responseData = $response->json();
            Log::info('PhonePe Status Response', [
                'transaction_id' => $merchantTransactionId,
                'status' => $response->status(),
                'response' => $responseData
            ]);

            if (!$response->successful()) {
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

            return [
                'success' => (bool)($responseData['success'] ?? false),
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
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'transactionId' => $merchantTransactionId,
                'paymentState' => 'ERROR',
                'message' => 'Error checking payment status: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate unique transaction ID
     */
    private function generateTransactionId()
    {
        return 'TXN_' . time() . '_' . rand(1000, 9999);
    }

    /**
     * Convert amount to paise
     */
    private function convertToPaise($amount)
    {
        return (int)($amount * 100);
    }

    /**
     * Format mobile number to 10 digits
     */
    private function formatMobileNumber($mobile)
    {
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        return strlen($mobile) > 10 ? substr($mobile, -10) : $mobile;
    }

    /**
     * Generate checksum for PhonePe API
     */
    private function generateChecksum($base64Payload, $apiPath)
    {
        $checksumString = $base64Payload . $apiPath . $this->saltKey;
        return hash('sha256', $checksumString) . '###' . $this->saltIndex;
    }
}
