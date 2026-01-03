<?php

namespace App\Contracts;

interface WebhookSupportInterface
{
    /**
     * Handle webhook payload
     */
    public function handleWebhook(array $payload): array;

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool;
}