<?php

namespace App\Contracts;

interface WebhookSupportInterface
{
    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool;

    /**
     * Parse webhook payload
     */
    public function parseWebhookPayload(string $payload): array;
}
