<?php

namespace App\Services;

use Exception;
use App\Models\User;

class AiCostCalculatorService
{
    // Model pricing per 1M tokens (input/output)
    private const MODEL_COSTS = [
        // Gemini models
        'gemini-1.5-flash' => ['input' => 0.075, 'output' => 0.30],
        'gemini-1.5-pro' => ['input' => 1.25, 'output' => 5.00],
        'gemini-2.0-flash-exp' => ['input' => 0.00, 'output' => 0.00],
        
        // Claude models
        'claude-3-5-sonnet-20241022' => ['input' => 3.00, 'output' => 15.00],
        'claude-3-5-haiku-20241022' => ['input' => 0.80, 'output' => 4.00],
        'claude-3-opus-20240229' => ['input' => 15.00, 'output' => 75.00],
    ];

    // Chat limits per plan
    private const CHAT_LIMITS = [
        'free' => 0,          
        'pro' => 100,          
        'pro_plus' => 500,     
        'enterprise' => -1,    
    ];

    // Case limits per plan
    private const CASE_LIMITS = [
        'free' => 0,
        'pro' => 3,
        'pro_plus' => -1,  // unlimited
        'enterprise' => -1,
    ];

    // Document limits per plan
    private const DOCUMENT_LIMITS = [
        'free' => 1,
        'pro' => -1,  // unlimited
        'pro_plus' => -1,
        'enterprise' => -1,
    ];

    // Message limits per plan
    private const MESSAGE_LIMITS = [
        'free' => 10,
        'pro' => -1,  // unlimited
        'pro_plus' => -1,
        'enterprise' => -1,
    ];

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
        
        // -1 means unlimited
        if ($limit === -1) {
            return false;
        }

        // Get current usage
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
            return -1; // unlimited
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
            return -1; // unlimited
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
            return -1; // unlimited
        }

        $currentUsage = $user->currentUsage;
        $documentsUsed = $currentUsage?->documents_generated ?? 0;

        return max(0, $limit - $documentsUsed);
    }

    /**
     * Get the appropriate model for a plan tier
     */
    public function getModelForPlan(string $planTier): string
    {
        $provider = config('services.ai.default_provider', 'gemini');
        
        if ($provider === 'gemini' && config('services.gemini.model')) {
            return config('services.gemini.model');
        }
        
        if ($provider === 'anthropic' && config('services.anthropic.model')) {
            return config('services.anthropic.model');
        }
  
        if ($provider === 'anthropic') {
            return match ($planTier) {
                'free' => 'claude-3-5-haiku-20241022',
                'pro' => 'claude-3-5-sonnet-20241022',
                'pro_plus' => 'claude-3-5-sonnet-20241022',
                'enterprise' => 'claude-3-opus-20240229',
                default => 'claude-3-5-sonnet-20241022',
            };
        }

        return match ($planTier) {
            'free' => 'gemini-1.5-flash',     
            'pro' => 'gemini-1.5-flash',       
            'pro_plus' => 'gemini-1.5-pro',    
            'enterprise' => 'gemini-1.5-pro',  
            default => 'gemini-1.5-flash',
        };
    }

    /**
     * Calculate cost for API usage
     */
    public function calculateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        if (!isset(self::MODEL_COSTS[$model])) {
            return 0.0;
        }

        $costs = self::MODEL_COSTS[$model];
        
        $inputCost = ($inputTokens / 1_000_000) * $costs['input'];
        $outputCost = ($outputTokens / 1_000_000) * $costs['output'];
        
        return round($inputCost + $outputCost, 6);
    }

    /**
     * Get cost threshold for a plan
     */
    public function getThreshold(string $planTier): float
    {
        return match ($planTier) {
            'free' => 0.0,
            'pro' => 5.0,
            'pro_plus' => 20.0,
            'enterprise' => 0.0,
            default => 0.0,
        };
    }

    /**
     * Get model information
     */
    public function getModelInfo(string $model): array
    {
        if (!isset(self::MODEL_COSTS[$model])) {
            return [
                'model' => $model,
                'input_cost_per_1m' => 0,
                'output_cost_per_1m' => 0,
                'provider' => 'unknown'
            ];
        }

        $costs = self::MODEL_COSTS[$model];
        $provider = str_starts_with($model, 'gemini') ? 'gemini' : 
                   (str_starts_with($model, 'claude') ? 'anthropic' : 'unknown');

        return [
            'model' => $model,
            'input_cost_per_1m' => $costs['input'],
            'output_cost_per_1m' => $costs['output'],
            'provider' => $provider
        ];
    }

    /**
     * Get all available models
     */
    public function getAvailableModels(): array
    {
        return array_keys(self::MODEL_COSTS);
    }

    /**
     * Check if model is available
     */
    public function isModelAvailable(string $model): bool
    {
        return isset(self::MODEL_COSTS[$model]);
    }

    /**
     * Get plan limits summary
     */
    public function getPlanLimits(string $planTier): array
    {
        return [
            'chat_limit' => $this->getChatLimit($planTier),
            'case_limit' => $this->getCaseLimit($planTier),
            'message_limit' => $this->getMessageLimit($planTier),
            'document_limit' => $this->getDocumentLimit($planTier),
            'cost_threshold' => $this->getThreshold($planTier),
            'model' => $this->getModelForPlan($planTier),
        ];
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
            'model' => $this->getModelForPlan($planTier),
            'cost_threshold' => $this->getThreshold($planTier),
        ];
    }
}