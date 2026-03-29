<?php

namespace App\Services\PaymentGateways;

use App\Services\PaymentGateways\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class RazorpayService implements PaymentGatewayInterface
{
    private $api;
    private $keyId;
    private $keySecret;

    public function __construct()
    {
        $this->keyId = config('payment.razorpay.key_id');
        $this->keySecret = config('payment.razorpay.key_secret');

        Log::info('Initializing RazorpayService', [
            'key_id' => $this->keyId ? 'SET' : 'MISSING',
        ]);

        $this->api = new Api($this->keyId, $this->keySecret);
    }

    public function initiatePayment(float $amount, int $userId, array $meta = []): array
    {
        try {
            Log::info('Initiating Razorpay payment', [
                'user_id' => $userId,
                'amount' => $amount,
                'meta' => $meta,
            ]);

            $amountInPaise = $amount * 100;

            $orderData = [
                'receipt' => 'order_' . time() . '_' . $userId,
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'notes' => array_merge([
                    'user_id' => $userId,
                    'created_at' => now()->toDateTimeString(),
                ], $meta),
            ];

            $order = $this->api->order->create($orderData);

            Log::info('Razorpay order created', [
                'order_id' => $order->id,
                'user_id' => $userId,
                'amount' => $amount,
                'raw_response' => $order->toArray(),
            ]);

            return [
                'success' => true,
                'order_id' => $order->id,
                'amount' => $amountInPaise,
                'currency' => $order->currency,
                'key_id' => $this->keyId,
                'merchant_transaction_id' => $order->id,
                'payment_url' => $order?->notes->returnUrl ?? '',
            ];
        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create payment order: ' . $e->getMessage(),
            ];
        }
    }

    public function verifyPaymentSignature($orderId, $paymentId, $signature)
    {
        try {
            Log::info('Verifying Razorpay signature', [
                'order_id' => $orderId,
                'payment_id' => $paymentId,
            ]);

            $attributes = [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ];

            $this->api->utility->verifyPaymentSignature($attributes);

            Log::info('Razorpay signature verified successfully', [
                'order_id' => $orderId,
                'payment_id' => $paymentId,
            ]);

            return [
                'success' => true,
                'verified' => true,
            ];
        } catch (\Exception $e) {
            Log::error('Razorpay signature verification failed', [
                'order_id' => $orderId,
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'verified' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function checkPaymentStatus($merchantTransactionId)
    {
        Log::info('Checking payment status from session', [
            'merchant_transaction_id' => $merchantTransactionId,
        ]);

        $orderId = session()->get('order_id');
        $paymentId = session()->get('payment_id');
        $signature = session()->get('signature');

        $result = $this->verifyPaymentSignature($orderId, $paymentId, $signature);

        if ($result['success'] && $result['verified']) {
            return $this->getPaymentDetail($paymentId);
        }

        Log::warning('Payment verification failed during status check', [
            'order_id' => $orderId,
            'payment_id' => $paymentId,
        ]);
    }

    public function getPaymentDetails($paymentId)
    {
        try {
            Log::info('Fetching Razorpay payment details', [
                'payment_id' => $paymentId,
            ]);

            $payment = $this->api->payment->fetch($paymentId);

            return [
                'success' => true,
                'payment' => [
                    'id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'amount' => $payment->amount / 100,
                    'currency' => $payment->currency,
                    'status' => $payment->status,
                    'method' => $payment->method,
                    'email' => $payment->email ?? null,
                    'contact' => $payment->contact ?? null,
                    'created_at' => date('Y-m-d H:i:s', $payment->created_at),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch Razorpay payment details', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to fetch payment details: ' . $e->getMessage(),
            ];
        }
    }

    public function getPaymentDetail(string $merchantTransactionId): array
    {
        try {
            Log::info('Checking Razorpay payment status', [
                'payment_id' => $merchantTransactionId,
            ]);

            $payment = $this->api->payment->fetch($merchantTransactionId);

            $paymentState = 'PENDING';
            if ($payment->status === 'captured' || $payment->status === 'authorized') {
                $paymentState = 'COMPLETED';
            } elseif ($payment->status === 'failed') {
                $paymentState = 'FAILED';
            }

            Log::info('Payment status evaluated', [
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'mapped_state' => $paymentState,
            ]);

            return [
                'success' => true,
                'paymentState' => $paymentState,
                'responseData' => [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'amount' => $payment->amount / 100,
                    'status' => $payment->status,
                    'method' => $payment->method,
                    'created_at' => date('Y-m-d H:i:s', $payment->created_at),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Razorpay payment status check failed', [
                'payment_id' => $merchantTransactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'paymentState' => 'FAILED',
                'message' => $e->getMessage(),
            ];
        }
    }

    public function processWebhook($payload, $signature)
    {
        try {
            Log::info('Processing Razorpay webhook', [
                'event' => $payload['event'] ?? 'unknown',
            ]);

            $webhookSecret = config('services.razorpay.webhook_secret');

            $this->api->utility->verifyWebhookSignature(
                json_encode($payload),
                $signature,
                $webhookSecret
            );

            Log::info('Webhook signature verified');

            $event = $payload['event'];
            $paymentEntity = $payload['payload']['payment']['entity'] ?? null;

            if (! $paymentEntity) {
                Log::warning('Invalid webhook payload received');

                return [
                    'success' => false,
                    'message' => 'Invalid webhook payload',
                ];
            }

            return [
                'success' => true,
                'event' => $event,
                'payment_id' => $paymentEntity['id'],
                'order_id' => $paymentEntity['order_id'],
                'status' => $paymentEntity['status'],
                'amount' => $paymentEntity['amount'] / 100,
            ];
        } catch (\Exception $e) {
            Log::error('Razorpay webhook processing failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Webhook verification failed: ' . $e->getMessage(),
            ];
        }
    }

    private function generateCheckoutUrl($order, array $options): string
    {
        Log::info('Generating checkout URL', [
            'order_id' => $order->id,
        ]);

        $callbackUrl = $options['redirectUrl'] ?? route('subscription.callback');

        return route('razorpay.checkout', [
            'order_id' => $order->id,
            'callback' => $callbackUrl,
        ]);
    }
}
