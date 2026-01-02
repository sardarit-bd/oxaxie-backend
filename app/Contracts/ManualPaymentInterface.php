<?php

namespace App\Contracts;

interface ManualPaymentInterface extends PaymentGatewayInterface
{
    /**
     * Record a manual payment
     */
    public function recordPayment(array $data): array;

    /**
     * Verify manual payment
     */
    public function verifyPayment(string $transactionId, array $verificationData): array;

    /**
     * Mark payment as received
     */
    public function markAsReceived(string $transactionId): array;
}
