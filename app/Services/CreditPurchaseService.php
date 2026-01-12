<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\Subscription;
use App\Models\UsageTracking;
use App\Models\CreditPurchase;
use App\Repositories\SubscriptionRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\CreditPurchaseRepository;

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
        $subscription = Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->first();
        
        if (!$subscription) {
            return 0.0;
        }

        // Total purchased credits for current billing cycle
        $totalPurchased = CreditPurchase::where('user_id', $userId)
            ->where('subscription_id', $subscription->id)
            ->where('status', 'completed')
            ->whereBetween('created_at', [
                $subscription->current_period_start,
                $subscription->current_period_end
            ])
            ->sum('credits_added');

        // Credits used from usage_tracking
        $usageTracking = UsageTracking::where('user_id', $userId)
            ->where('billing_cycle_date', Carbon::parse($subscription->current_period_start)->toDateString())
            ->first();

        $creditsUsed = $usageTracking->credits_used ?? 0.0;

        return max(0, $totalPurchased - $creditsUsed);
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