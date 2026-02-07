<?php

namespace App\Services\PaymentGateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PhonePeService
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

        if ($this->isProduction) {
            $this->baseUrl = 'https://api.phonepe.com/apis/hermes';
        } else {
            $this->baseUrl = 'https://api-preprod.phonepe.com/apis/pg-sandbox';
        }
    }

    public function initiatePayment($amount, $userId, $planName)
    {
        $merchantTransactionId = 'ORDER_'.time().rand(1000, 9999);

        $payload = [
            'merchantId' => $this->merchantId,
            'merchantTransactionId' => $merchantTransactionId,
            'merchantUserId' => 'MUID'.$userId,
            'amount' => $amount * 100, // Convert to paise
            'redirectUrl' => route('subscribe.callback'),
            'redirectMode' => 'POST',
            'callbackUrl' => route('subscribe.callback'),
            'paymentInstrument' => [
                'type' => 'PAY_PAGE',
            ],
        ];

        $jsonPayload = json_encode($payload);
        $base64Payload = base64_encode($jsonPayload);

        // Fix: Add space after '###'
        $checksumString = $base64Payload.'/pg/v1/pay'.$this->saltKey;
        $checksum = hash('sha256', $checksumString).'###'.$this->saltIndex;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-VERIFY' => $checksum,
            'accept' => 'application/json',
        ])->post($this->baseUrl.'/pg/v1/pay', [
            'request' => $base64Payload,
        ]);

        $result = $response->json();

        // Log for debugging
        Log::info('PhonePe Response:', $result);

        if (isset($result['success']) && $result['success'] === true) {
            return [
                'success' => true,
                'payment_url' => $result['data']['instrumentResponse']['redirectInfo']['url'],
                'merchant_transaction_id' => $merchantTransactionId,
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Payment initiation failed',
            'error_code' => $result['code'] ?? null,
        ];
    }

    public function checkPaymentStatus($merchantTransactionId)
    {
        $checksumString = '/pg/v1/status/'.$this->merchantId.'/'.$merchantTransactionId.$this->saltKey;
        $checksum = hash('sha256', $checksumString).'###'.$this->saltIndex;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-VERIFY' => $checksum,
            'X-MERCHANT-ID' => $this->merchantId,
        ])->get($this->baseUrl.'/pg/v1/status/'.$this->merchantId.'/'.$merchantTransactionId);

        return $response;
    }
}
