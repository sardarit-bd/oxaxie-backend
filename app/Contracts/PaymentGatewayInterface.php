<?php

namespace App\Contracts;

interface PaymentGatewayInterface
{
    /**
     * Process a payment
     */
    public function processPayment(array $data): array;

    /**
     * Get payment status
     */
    public function getPaymentStatus(string $transactionId): array;

    /**
     * Get gateway name
     */
    public function getGatewayName(): string;

    /**
     * Check if gateway is available
     */
    public function isAvailable(): bool;
}
