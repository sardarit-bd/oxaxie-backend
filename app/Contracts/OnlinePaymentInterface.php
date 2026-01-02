<?php

namespace App\Contracts;

interface OnlinePaymentInterface extends PaymentGatewayInterface
{
    /**
     * Create a payment intent (for card payments, etc.)
     */
    public function createPaymentIntent(array $data): array;

    /**
     * Retrieve a payment intent
     */
    public function retrievePaymentIntent(string $paymentIntentId): array;

    /**
     * Confirm a payment
     */
    public function confirmPayment(string $paymentIntentId): array;
}
