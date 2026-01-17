<?php

namespace App\Services;

use App\Models\User;
use App\Models\AiModel;
use App\Models\AiModelPricing;
use App\Repositories\Contracts\AiModelPricingRepositoryInterface;
use Exception;

class AiCostCalculatorService
{
    protected AiModelPricingRepositoryInterface $pricingRepository;

    // Chat limits per plan
    private const CHAT_LIMITS = [
        'free' => 1,          
        'pro' => 100,          
        'pro_plus' => 500,     
        'enterprise' => -1,    
    ];

    // Case limits per plan
    private const CASE_LIMITS = [
        'free' => 1,
        'pro' => 3,
        'pro_plus' => -1, 
        'enterprise' => -1,
    ];

    // Document limits per plan
    private const DOCUMENT_LIMITS = [
        'free' => 1,
        'pro' => -1, 
        'pro_plus' => -1,
        'enterprise' => -1,
    ];

    // Message limits per plan
    private const MESSAGE_LIMITS = [
        'free' => 10,
        'pro' => -1, 
        'pro_plus' => -1,
        'enterprise' => -1,
    ];

    public function __construct(AiModelPricingRepositoryInterface $pricingRepository)
    {
        $this->pricingRepository = $pricingRepository;
    }

    /**
     * Calculate cost for API usage
     */
    public function calculateCost(string $modelName, int $inputTokens, int $outputTokens, ?string $planTier = null): float
    {
        // Find the model
        $model = AiModel::where('name', $modelName)->first();

        if (!$model) {
            return 0.0;
        }

        // Get pricing for the model and plan
        $pricing = $this->pricingRepository->getPricingForModel($model->id, $planTier);

        // If no plan-specific pricing, get default pricing
        if (!$pricing) {
            $pricing = $this->pricingRepository->getPricingForModel($model->id, null);
        }

        if (!$pricing) {
            return 0.0;
        }

        $inputCost = ($inputTokens / 1_000_000) * $pricing->input_cost_per_1m_tokens;
        $outputCost = ($outputTokens / 1_000_000) * $pricing->output_cost_per_1m_tokens;
        
        return round($inputCost + $outputCost, 6);
    }

    /**
     * Calculate cost by model ID
     */
    public function calculateCostByModelId(int $modelId, int $inputTokens, int $outputTokens, ?string $planTier = null): float
    {
        $pricing = $this->pricingRepository->getPricingForModel($modelId, $planTier);

        if (!$pricing) {
            $pricing = $this->pricingRepository->getPricingForModel($modelId, null);
        }

        if (!$pricing) {
            return 0.0;
        }

        $inputCost = ($inputTokens / 1_000_000) * $pricing->input_cost_per_1m_tokens;
        $outputCost = ($outputTokens / 1_000_000) * $pricing->output_cost_per_1m_tokens;
        
        return round($inputCost + $outputCost, 6);
    }

    /**
     * Get pricing for a model
     */
    public function getModelPricing(int $modelId, ?string $planTier = null): ?AiModelPricing
    {
        $pricing = $this->pricingRepository->getPricingForModel($modelId, $planTier);

        if (!$pricing) {
            $pricing = $this->pricingRepository->getPricingForModel($modelId, null);
        }

        return $pricing;
    }

    /**
     * Get chat message limit for a plan
     */
    public function getChatLimit(string $planTier): int
    {
        return self::CHAT_LIMITS[$planTier] ?? 0;
    }

    /**
     * Get case limit for a plan
     */
    public function getCaseLimit(string $planTier): int
    {
        return self::CASE_LIMITS[$planTier] ?? 0;
    }

    /**
     * Get document limit for a plan
     */
    public function getDocumentLimit(string $planTier): int
    {
        return self::DOCUMENT_LIMITS[$planTier] ?? 1;
    }

    /**
     * Get message limit for a plan
     */
    public function getMessageLimit(string $planTier): int
    {
        return self::MESSAGE_LIMITS[$planTier] ?? 10;
    }

    /**
     * Check if user has reached their case limit
     */
    public function hasReachedCaseLimit(User $user): bool
    {
        $planTier = $user->subscription?->plan_tier ?? 'free';
        $limit = $this->getCaseLimit($planTier);
        
        if ($limit === -1) {
            return false;
        }

        $currentUsage = $user->currentUsage;
        $casesUsed = $currentUsage?->cases_created ?? 0;

        return $casesUsed >= $limit;
    }

    /**
     * Check if user has reached their message limit
     */
    public function hasReachedMessageLimit(User $user): bool
    {
        $planTier = $user->subscription?->plan_tier ?? 'free';
        $limit = $this->getMessageLimit($planTier);
        
        if ($limit === -1) {
            return false;
        }

        $currentUsage = $user->currentUsage;
        $messagesUsed = $currentUsage?->messages_used ?? 0;

        return $messagesUsed >= $limit;
    }

    /**
     * Check if user has reached their document limit
     */
    public function hasReachedDocumentLimit(User $user): bool
    {
        $planTier = $user->subscription?->plan_tier ?? 'free';
        $limit = $this->getDocumentLimit($planTier);
        
        if ($limit === -1) {
            return false;
        }

        $currentUsage = $user->currentUsage;
        $documentsUsed = $currentUsage?->documents_generated ?? 0;

        return $documentsUsed >= $limit;
    }

    /**
     * Get remaining cases for user
     */
    public function getRemainingCases(User $user): int
    {
        $planTier = $user->subscription?->plan_tier ?? 'free';
        $limit = $this->getCaseLimit($planTier);
        
        if ($limit === -1) {
            return -1;
        }

        $currentUsage = $user->currentUsage;
        $casesUsed = $currentUsage?->cases_created ?? 0;

        return max(0, $limit - $casesUsed);
    }

    /**
     * Get remaining messages for user
     */
    public function getRemainingMessages(User $user): int
    {
        $planTier = $user->subscription?->plan_tier ?? 'free';
        $limit = $this->getMessageLimit($planTier);
        
        if ($limit === -1) {
            return -1;
        }

        $currentUsage = $user->currentUsage;
        $messagesUsed = $currentUsage?->messages_used ?? 0;

        return max(0, $limit - $messagesUsed);
    }

    /**
     * Get remaining documents for user
     */
    public function getRemainingDocuments(User $user): int
    {
        $planTier = $user->subscription?->plan_tier ?? 'free';
        $limit = $this->getDocumentLimit($planTier);
        
        if ($limit === -1) {
            return -1;
        }

        $currentUsage = $user->currentUsage;
        $documentsUsed = $currentUsage?->documents_generated ?? 0;

        return max(0, $limit - $documentsUsed);
    }

    /**
     * Get cost threshold for a plan
     */
    public function getThreshold(string $planTier): float
    {
        if (config('app.env') === 'local') {
            return match ($planTier) {
                'free' => 0.0,
                'pro' => 0.05,  
                'pro_plus' => 0.15,
                'enterprise' => 0.0,
                default => 0.0,
            };
        }

        return match ($planTier) {
            'free' => 0.0,
            'pro' => 5.0,
            'pro_plus' => 19.0,
            'enterprise' => 0.0,
            default => 0.0,
        };
    }

    /**
     * Get all limits for a user
     */
    public function getUserLimits(User $user): array
    {
        $planTier = $user->subscription?->plan_tier ?? 'free';
        
        return [
            'plan_tier' => $planTier,
            'limits' => [
                'cases' => [
                    'limit' => $this->getCaseLimit($planTier),
                    'used' => $user->currentUsage?->cases_created ?? 0,
                    'remaining' => $this->getRemainingCases($user),
                ],
                'messages' => [
                    'limit' => $this->getMessageLimit($planTier),
                    'used' => $user->currentUsage?->messages_used ?? 0,
                    'remaining' => $this->getRemainingMessages($user),
                ],
                'documents' => [
                    'limit' => $this->getDocumentLimit($planTier),
                    'used' => $user->currentUsage?->documents_generated ?? 0,
                    'remaining' => $this->getRemainingDocuments($user),
                ],
            ],
            'cost_threshold' => $this->getThreshold($planTier),
        ];
    }
}