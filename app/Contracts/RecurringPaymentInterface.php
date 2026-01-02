<?php

namespace App\Contracts;

interface RecurringPaymentInterface
{
    /**
     * Create a subscription
     */
    public function createSubscription(array $data): array;

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId): array;

    /**
     * Get subscription details
     */
    public function getSubscription(string $subscriptionId): array;
}
