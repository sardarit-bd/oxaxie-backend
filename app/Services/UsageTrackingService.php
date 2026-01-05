<?php

namespace App\Services;

use App\Repositories\UsageTrackingRepository;
use App\Repositories\SubscriptionRepository;
use App\Models\UsageTracking;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Exception;

class UsageTrackingService
{
    public function __construct(
        protected UsageTrackingRepository $usageTrackingRepository,
        protected SubscriptionRepository $subscriptionRepository
    ) {}

    /**
     * Record or update usage for a billing cycle
     */
    public function recordUsage(string $userId, array $data): array
    {
        $subscription = $this->subscriptionRepository->findByUserId($userId);
        
        $usageData = [
            'subscription_id' => $subscription?->id,
            'messages_used' => $data['messages_used'] ?? 0,
            'documents_generated' => $data['documents_generated'] ?? 0,
            'cases_created' => $data['cases_created'] ?? 0,
            'ai_cost_accumulated' => $data['ai_cost_accumulated'] ?? 0.0000,
            'input_tokens_used' => $data['input_tokens_used'] ?? 0,
            'output_tokens_used' => $data['output_tokens_used'] ?? 0,
        ];

        $usageTracking = $this->usageTrackingRepository->updateOrCreate(
            $userId,
            $data['billing_cycle_date'],
            $usageData
        );

        return [
            'usage_tracking' => $usageTracking,
            'was_created' => $usageTracking->wasRecentlyCreated,
        ];
    }

    /**
     * Increment usage counters
     */
    public function incrementUsage(string $userId, array $data): UsageTracking
    {
        $billingCycleDate = $data['billing_cycle_date'] ?? Carbon::today()->toDateString();
        
        $subscription = $this->subscriptionRepository->findByUserId($userId);
        
        // Get or create usage tracking record
        $usageTracking = $this->usageTrackingRepository->findOrCreateByBillingCycle(
            $userId,
            $billingCycleDate,
            $subscription?->id
        );

        // Build increments array
        $increments = [];
        
        if (isset($data['messages_used']) && $data['messages_used'] > 0) {
            $increments['messages_used'] = $data['messages_used'];
        }
        if (isset($data['documents_generated']) && $data['documents_generated'] > 0) {
            $increments['documents_generated'] = $data['documents_generated'];
        }
        if (isset($data['cases_created']) && $data['cases_created'] > 0) {
            $increments['cases_created'] = $data['cases_created'];
        }
        if (isset($data['input_tokens_used']) && $data['input_tokens_used'] > 0) {
            $increments['input_tokens_used'] = $data['input_tokens_used'];
        }
        if (isset($data['output_tokens_used']) && $data['output_tokens_used'] > 0) {
            $increments['output_tokens_used'] = $data['output_tokens_used'];
        }
        if (isset($data['ai_cost_accumulated']) && $data['ai_cost_accumulated'] > 0) {
            $increments['ai_cost_accumulated'] = $data['ai_cost_accumulated'];
        }

        // Increment counters
        $this->usageTrackingRepository->incrementCounters($usageTracking, $increments);

        return $usageTracking->fresh();
    }

    /**
     * Get current billing cycle usage
     */
    public function getCurrentUsage(string $userId): array
    {
        $today = Carbon::today()->toDateString();
        
        $usageTracking = $this->usageTrackingRepository->getCurrentUsage($userId, $today);

        if (!$usageTracking) {
            return [
                'messages_used' => 0,
                'documents_generated' => 0,
                'cases_created' => 0,
                'ai_cost_accumulated' => 0.0000,
                'input_tokens_used' => 0,
                'output_tokens_used' => 0,
                'cost_threshold_reached' => false,
            ];
        }

        return $usageTracking->toArray();
    }

    /**
     * Get usage history
     */
    public function getUsageHistory(string $userId, ?string $startDate = null, ?string $endDate = null, int $limit = 30): Collection
    {
        return $this->usageTrackingRepository->getHistory($userId, $startDate, $endDate, $limit);
    }

    /**
     * Get usage summary
     */
    public function getUsageSummary(string $userId, ?string $startDate = null, ?string $endDate = null): array
    {
        return $this->usageTrackingRepository->getSummary($userId, $startDate, $endDate);
    }

    /**
     * Check and update cost threshold
     */
    public function checkCostThreshold(string $userId, string $billingCycleDate, float $thresholdAmount): array
    {
        $usageTracking = $this->usageTrackingRepository->getCurrentUsage($userId, $billingCycleDate);
        
        if (!$usageTracking) {
            throw new Exception('Usage tracking record not found');
        }

        $thresholdReached = false;

        // Check if threshold reached and update if necessary
        if ($usageTracking->ai_cost_accumulated >= $thresholdAmount && !$usageTracking->cost_threshold_reached) {
            $this->usageTrackingRepository->update($usageTracking, [
                'cost_threshold_reached' => true,
                'threshold_reached_at' => now(),
            ]);
            
            $thresholdReached = true;
            $usageTracking = $usageTracking->fresh();
        }

        return [
            'threshold_reached' => $thresholdReached,
            'usage_tracking' => $usageTracking,
        ];
    }

    /**
     * Get specific usage tracking record
     */
    public function getUsageById(string $userId, string $usageId): UsageTracking
    {
        $usageTracking = $this->usageTrackingRepository->findByIdAndUser($usageId, $userId);
        
        if (!$usageTracking) {
            throw new Exception('Usage tracking record not found');
        }

        return $usageTracking;
    }
}