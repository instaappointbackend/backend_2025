<?php

namespace App\Services\PaymentGateways\Contracts;

interface PaymentGatewayInterface
{
    public function initiatePayment(float $amount, int $userId, array $metadata = []): array;

    public function checkPaymentStatus(string $transactionId);
}
