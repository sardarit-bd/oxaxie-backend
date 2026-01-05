<?php

namespace App\Services;

use App\Repositories\CreditPurchaseRepository;
use App\Repositories\SubscriptionRepository;
use App\Models\CreditPurchase;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class CreditPurchaseService
{
    public function __construct(
        protected CreditPurchaseRepository $creditPurchaseRepository,
        protected SubscriptionRepository $subscriptionRepository
    ) {}

    /**
     * Create a new credit purchase
     */
    public function createPurchase(string $userId, array $data): CreditPurchase
    {
        // Check if user has a subscription
        $subscription = $this->subscriptionRepository->findByUserId($userId);
        
        if (!$subscription) {
            throw new Exception('No active subscription found');
        }

        // Check for duplicate payment intent
        if (isset($data['stripe_payment_intent_id'])) {
            $existingPurchase = $this->creditPurchaseRepository
                ->findByPaymentIntentId($data['stripe_payment_intent_id']);
            
            if ($existingPurchase) {
                throw new Exception('This payment has already been processed');
            }
        }

        // Prepare purchase data
        $purchaseData = [
            'user_id' => $userId,
            'subscription_id' => $subscription->id,
            'amount' => $data['amount'],
            'credits_added' => $data['credits_added'],
            'expires_at' => $data['expires_at'],
            'stripe_payment_intent_id' => $data['stripe_payment_intent_id'] ?? null,
            'status' => 'pending',
        ];

        return $this->creditPurchaseRepository->create($purchaseData);
    }

    /**
     * Update credit purchase status
     */
    public function updateStatus(string $userId, string $purchaseId, string $status): CreditPurchase
    {
        $creditPurchase = $this->creditPurchaseRepository->findByIdAndUser($purchaseId, $userId);
        
        if (!$creditPurchase) {
            throw new Exception('Credit purchase not found');
        }

        $updateData = ['status' => $status];
        
        // Set applied_at timestamp when completing
        if ($status === 'completed' && !$creditPurchase->applied_at) {
            $updateData['applied_at'] = now();
        }

        $this->creditPurchaseRepository->update($creditPurchase, $updateData);
        
        return $creditPurchase->fresh();
    }

    /**
     * Get all purchases for a user
     */
    public function getUserPurchases(string $userId): Collection
    {
        return $this->creditPurchaseRepository->getAllByUser($userId);
    }

    /**
     * Get purchase history with summary
     */
    public function getPurchaseHistory(string $userId): array
    {
        $purchases = $this->creditPurchaseRepository->getAllByUser($userId);
        $summary = $this->creditPurchaseRepository->getCompletedSummary($userId);

        return [
            'purchases' => $purchases,
            'summary' => $summary,
        ];
    }

    /**
     * Get available credits
     */
    public function getAvailableCredits(string $userId): float
    {
        return $this->creditPurchaseRepository->getAvailableCredits($userId);
    }

    /**
     * Get specific purchase
     */
    public function getPurchaseById(string $userId, string $purchaseId): CreditPurchase
    {
        $creditPurchase = $this->creditPurchaseRepository->findByIdAndUser($purchaseId, $userId);
        
        if (!$creditPurchase) {
            throw new Exception('Credit purchase not found');
        }

        return $creditPurchase;
    }
}