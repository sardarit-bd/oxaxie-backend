<?php

namespace App\Contracts;

interface RefundablePaymentInterface
{
     /**
     * Refund a payment
     */
    public function refundPayment(string $transactionId, ?float $amount = null): array;

    /**
     * Check if payment can be refunded
     */
    public function canRefund(string $transactionId): bool;
}
