<?php

namespace App\Contracts;

interface CustomerVerificationInterface
{
    /**
     * Verify customer identity
     */
    public function verifyCustomer(array $customerData): array;

    /**
     * Get verification status
     */
    public function getVerificationStatus(string $verificationId): array;
}
