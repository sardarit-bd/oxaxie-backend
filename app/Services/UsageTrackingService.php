<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\UsageTracking;
use App\Services\CreditPurchaseService;
use App\Repositories\SubscriptionRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\UsageTrackingRepository;

class UsageTrackingService
{
    public function __construct(
        protected UsageTrackingRepository $usageTrackingRepository,
        protected SubscriptionRepository $subscriptionRepository,
        protected AiCostCalculatorService $costCalculator,
        protected CreditPurchaseService $creditPurchaseService
    ) {}

    /**
     * Track AI usage after successful response
     */
    public function trackAiUsage(
        string $userId,
        string $model,
        int $inputTokens,
        int $outputTokens
    ): array {
        // Get subscription
        $subscription = $this->subscriptionRepository->findByUserId($userId);
        if (!$subscription) {
            throw new Exception('No active subscription found');
        }

        // Calculate cost
        $cost = $this->costCalculator->calculateCost($model, $inputTokens, $outputTokens);
        
        // Get billing cycle date
        $billingCycleDate = Carbon::parse($subscription->current_period_start)->toDateString();
        
        // Get or create usage tracking
        $usageTracking = $this->usageTrackingRepository->findOrCreateByBillingCycle(
            $userId,
            $billingCycleDate,
            $subscription->id
        );

        // Calculate new accumulated cost
        $newCostAccumulated = $usageTracking->ai_cost_accumulated + $cost;
        $planTier = $subscription->plan_tier;
        $threshold = $this->costCalculator->getThreshold($planTier);

        $creditsUsed = $usageTracking->credits_used ?? 0.0;
        $thresholdReached = false;
        $needsCredits = false;

        if (in_array($planTier, ['pro', 'pro_plus'])) {
            if ($planTier === 'pro_plus') {
     
                $availableCredits = $this->creditPurchaseService->getAvailableCredits($userId);
                $totalLimit = $threshold + $availableCredits;
                
                // Calculate how much of the new cost goes to credits
                if ($newCostAccumulated > $threshold) {
                    $excessCost = $newCostAccumulated - $threshold;
                    $newCreditsUsed = min($excessCost, $availableCredits);
                    $creditsUsed = $newCreditsUsed;
                    
                    // Check if we've exceeded total limit
                    if ($newCostAccumulated >= $totalLimit) {
                        $thresholdReached = true;
                        $needsCredits = true;
                    }
                } else {
                    $creditsUsed = 0.0;
                }
            } else {
     
                if ($newCostAccumulated >= $threshold) {
                    $thresholdReached = true;
                }
            }
        }

        // Update usage tracking
        $usageTracking->update([
            'messages_used' => $usageTracking->messages_used + 1,
            'ai_cost_accumulated' => $newCostAccumulated,
            'input_tokens_used' => $usageTracking->input_tokens_used + $inputTokens,
            'output_tokens_used' => $usageTracking->output_tokens_used + $outputTokens,
            'credits_used' => $creditsUsed,
            'cost_threshold_reached' => $thresholdReached,
            'threshold_reached_at' => $thresholdReached && !$usageTracking->cost_threshold_reached ? now() : $usageTracking->threshold_reached_at,
        ]);

        return [
            'usage_updated' => true,
            'cost_added' => $cost,
            'total_cost' => $newCostAccumulated,
            'threshold_reached' => $thresholdReached,
            'needs_credits' => $needsCredits,
            'credits_used' => $creditsUsed,
            'available_credits' => $planTier === 'pro_plus' 
                ? $this->creditPurchaseService->getAvailableCredits($userId) 
                : 0,
        ];
    }

    /**
     * Original incrementUsage - kept for backward compatibility
     */
    public function incrementUsage(string $userId, array $data): UsageTracking
    {
        $billingCycleDate = $data['billing_cycle_date'] ?? Carbon::today()->toDateString();
        
        $subscription = $this->subscriptionRepository->findByUserId($userId);
        
        $usageTracking = $this->usageTrackingRepository->findOrCreateByBillingCycle(
            $userId,
            $billingCycleDate,
            $subscription?->id
        );

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