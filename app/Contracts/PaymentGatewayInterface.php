<?php

namespace App\Contracts;

/**
 * Base Payment Gateway Interface
 * All payment gateways must implement this
 */
interface PaymentGatewayInterface
{
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