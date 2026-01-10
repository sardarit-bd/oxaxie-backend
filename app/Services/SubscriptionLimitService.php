<?php

namespace App\Services;

use App\Repositories\SubscriptionRepository;
use App\Repositories\UsageTrackingRepository;
use Carbon\Carbon;
use Exception;

/**
 * Subscription Limit Service
 * Checks if user has access to features and hasn't exceeded limits
 */
class SubscriptionLimitService
{
    public function __construct(
        protected SubscriptionRepository $subscriptionRepository,
        protected UsageTrackingRepository $usageTrackingRepository,
        protected AiCostCalculatorService $costCalculator
    ) {}

    /**
     * Check if user can send chat message
     * 
     * @param string $userId User ID
     * @return array ['allowed' => bool, 'reason' => string|null, 'upgrade_to' => string|null]
     */
    public function canSendMessage(string $userId): array
    {
        $subscription = $this->subscriptionRepository->getActiveByUserId($userId);
        
        if (!$subscription) {
            return [
                'allowed' => false,
                'reason' => 'No active subscription found',
                'upgrade_to' => 'pro',
                'current_plan' => 'none',
            ];
        }

        $planTier = $subscription->plan_tier;
        $today = Carbon::today()->toDateString();
        $usage = $this->usageTrackingRepository->getCurrentUsage($userId, $today);

        // Get plan limits
        $chatLimit = $this->costCalculator->getChatLimit($planTier);
        $threshold = $this->costCalculator->getThreshold($planTier);

        // Free tier: Check fixed chat limit
        if ($planTier === 'free') {
            $messagesUsed = $usage->messages_used ?? 0;
            
            if ($messagesUsed >= $chatLimit) {
                return [
                    'allowed' => false,
                    'reason' => "You've reached your free plan limit of {$chatLimit} chats per month.",
                    'upgrade_to' => 'pro',
                    'current_plan' => 'free',
                    'limit' => $chatLimit,
                    'used' => $messagesUsed,
                ];
            }
            
            return [
                'allowed' => true,
                'current_plan' => 'free',
                'remaining' => $chatLimit - $messagesUsed,
            ];
        }

        // Pro and Pro Plus: Check cost threshold
        if ($planTier === 'pro' || $planTier === 'pro_plus') {
            $costAccumulated = $usage->ai_cost_accumulated ?? 0.0;
            
            if ($usage && $usage->cost_threshold_reached) {
                $upgradeTo = $planTier === 'pro' ? 'pro_plus' : null;
                
                return [
                    'allowed' => false,
                    'reason' => "You've reached your ${threshold} monthly threshold. Please upgrade to continue.",
                    'upgrade_to' => $upgradeTo,
                    'current_plan' => $planTier,
                    'threshold' => $threshold,
                    'cost_accumulated' => $costAccumulated,
                ];
            }
            
            return [
                'allowed' => true,
                'current_plan' => $planTier,
                'threshold' => $threshold,
                'cost_accumulated' => $costAccumulated,
                'remaining_cost' => $threshold - $costAccumulated,
            ];
        }

        return ['allowed' => true, 'current_plan' => $planTier];
    }

    /**
     * Check if user can create a case
     * 
     * @param string $userId User ID
     * @return array ['allowed' => bool, 'reason' => string|null]
     */
    public function canCreateCase(string $userId): array
    {
        $subscription = $this->subscriptionRepository->getActiveByUserId($userId);
        
        if (!$subscription) {
            return [
                'allowed' => false,
                'reason' => 'No active subscription found',
                'upgrade_to' => 'pro',
                'current_plan' => 'none',
            ];
        }

        $planTier = $subscription->plan_tier;
        $caseLimit = $this->costCalculator->getCaseLimit($planTier);

        // Free tier: 1 cases allowed
        if ($planTier === 'free') {
            return [
                'allowed' => true,
                'reason' => 'Case creation is not available on the free plan. Please upgrade to Pro.',
                'upgrade_to' => 'pro',
                'current_plan' => 'free',
            ];
        }

        // Pro: Check case limit
        if ($planTier === 'pro') {
            $today = Carbon::today()->toDateString();
            $usage = $this->usageTrackingRepository->getCurrentUsage($userId, $today);
            $casesCreated = $usage->cases_created ?? 0;
            
            if ($casesCreated >= $caseLimit) {
                return [
                    'allowed' => false,
                    'reason' => "You've reached your Pro plan limit of {$caseLimit} cases per month.",
                    'upgrade_to' => 'pro_plus',
                    'current_plan' => 'pro',
                    'limit' => $caseLimit,
                    'used' => $casesCreated,
                ];
            }
            
            return [
                'allowed' => true,
                'current_plan' => 'pro',
                'remaining' => $caseLimit - $casesCreated,
            ];
        }

        // Pro Plus: Unlimited cases
        return [
            'allowed' => true,
            'current_plan' => 'pro_plus',
        ];
    }

    /**
     * Check if user can generate document
     * 
     * @param string $userId User ID
     * @return array ['allowed' => bool, 'reason' => string|null]
     */
    public function canGenerateDocument(string $userId): array
    {
        $subscription = $this->subscriptionRepository->getActiveByUserId($userId);
        
        if (!$subscription) {
            return [
                'allowed' => false,
                'reason' => 'No active subscription found',
                'upgrade_to' => 'pro',
                'current_plan' => 'none',
            ];
        }

        $planTier = $subscription->plan_tier;
        $documentLimit = $this->costCalculator->getDocumentLimit($planTier);
        $today = Carbon::today()->toDateString();
        $usage = $this->usageTrackingRepository->getCurrentUsage($userId, $today);

        // Free tier: Check fixed document limit
        if ($planTier === 'free') {
            $documentsGenerated = $usage->documents_generated ?? 0;
            
            if ($documentsGenerated >= $documentLimit) {
                return [
                    'allowed' => false,
                    'reason' => "You've reached your free plan limit of {$documentLimit} document per month.",
                    'upgrade_to' => 'pro',
                    'current_plan' => 'free',
                    'limit' => $documentLimit,
                    'used' => $documentsGenerated,
                ];
            }
            
            return [
                'allowed' => true,
                'current_plan' => 'free',
                'remaining' => $documentLimit - $documentsGenerated,
            ];
        }

        // Pro and Pro Plus: Check cost threshold
        if ($planTier === 'pro' || $planTier === 'pro_plus') {
            $threshold = $this->costCalculator->getThreshold($planTier);
            $costAccumulated = $usage->ai_cost_accumulated ?? 0.0;
            
            if ($usage && $usage->cost_threshold_reached) {
                $upgradeTo = $planTier === 'pro' ? 'pro_plus' : null;
                
                return [
                    'allowed' => false,
                    'reason' => "You've reached your ${threshold} monthly threshold.",
                    'upgrade_to' => $upgradeTo,
                    'current_plan' => $planTier,
                ];
            }
            
            return [
                'allowed' => true,
                'current_plan' => $planTier,
            ];
        }

        return ['allowed' => true, 'current_plan' => $planTier];
    }

    /**
     * Get current usage summary for user
     * 
     * @param string $userId User ID
     * @return array Usage summary with limits
     */
    public function getUsageSummary(string $userId): array
    {
        $subscription = $this->subscriptionRepository->getActiveByUserId($userId);
        
        if (!$subscription) {
            return [
                'has_subscription' => false,
                'plan_tier' => 'none',
            ];
        }

        $planTier = $subscription->plan_tier;
        $today = Carbon::today()->toDateString();
        $usage = $this->usageTrackingRepository->getCurrentUsage($userId, $today);

        $chatLimit = $this->costCalculator->getChatLimit($planTier);
        $caseLimit = $this->costCalculator->getCaseLimit($planTier);
        $documentLimit = $this->costCalculator->getDocumentLimit($planTier);
        $threshold = $this->costCalculator->getThreshold($planTier);

        return [
            'has_subscription' => true,
            'plan_tier' => $planTier,
            'model' => $this->costCalculator->getModelForPlan($planTier),
            'messages' => [
                'used' => $usage->messages_used ?? 0,
                'limit' => $chatLimit,
                'remaining' => $chatLimit ? max(0, $chatLimit - ($usage->messages_used ?? 0)) : null,
            ],
            'cases' => [
                'created' => $usage->cases_created ?? 0,
                'limit' => $caseLimit,
                'remaining' => $caseLimit ? max(0, $caseLimit - ($usage->cases_created ?? 0)) : null,
            ],
            'documents' => [
                'generated' => $usage->documents_generated ?? 0,
                'limit' => $documentLimit,
                'remaining' => $documentLimit ? max(0, $documentLimit - ($usage->documents_generated ?? 0)) : null,
            ],
            'cost' => [
                'accumulated' => $usage->ai_cost_accumulated ?? 0.0,
                'threshold' => $threshold,
                'remaining' => $threshold > 0 ? max(0, $threshold - ($usage->ai_cost_accumulated ?? 0.0)) : null,
                'threshold_reached' => $usage->cost_threshold_reached ?? false,
            ],
            'tokens' => [
                'input_used' => $usage->input_tokens_used ?? 0,
                'output_used' => $usage->output_tokens_used ?? 0,
            ],
        ];
    }

    /**
     * Check if usage should show warning (80% threshold)
     * 
     * @param string $userId User ID
     * @return array|null Warning details or null
     */
    public function getUsageWarning(string $userId): ?array
    {
        $subscription = $this->subscriptionRepository->getActiveByUserId($userId);
        
        if (!$subscription) {
            return null;
        }

        $planTier = $subscription->plan_tier;
        $today = Carbon::today()->toDateString();
        $usage = $this->usageTrackingRepository->getCurrentUsage($userId, $today);

        if (!$usage) {
            return null;
        }

        // For free tier, check chat usage
        if ($planTier === 'free') {
            $chatLimit = $this->costCalculator->getChatLimit($planTier);
            $messagesUsed = $usage->messages_used;
            $percentage = ($messagesUsed / $chatLimit) * 100;
            
            if ($percentage >= 80) {
                return [
                    'type' => 'chat_limit',
                    'message' => "You've used {$messagesUsed} out of {$chatLimit} chats. Consider upgrading to Pro for unlimited chats.",
                    'percentage' => round($percentage, 2),
                    'upgrade_to' => 'pro',
                ];
            }
        }

        // For Pro/Pro Plus, check cost threshold
        if ($planTier === 'pro' || $planTier === 'pro_plus') {
            $threshold = $this->costCalculator->getThreshold($planTier);
            $costAccumulated = $usage->ai_cost_accumulated;
            $percentage = ($costAccumulated / $threshold) * 100;
            
            if ($percentage >= 80 && !$usage->cost_threshold_reached) {
                return [
                    'type' => 'cost_threshold',
                    'message' => "You've used $" . number_format($costAccumulated, 2) . " out of your $" . number_format($threshold, 2) . " monthly threshold.",
                    'percentage' => round($percentage, 2),
                    'upgrade_to' => $planTier === 'pro' ? 'pro_plus' : null,
                ];
            }
        }

        return null;
    }
}