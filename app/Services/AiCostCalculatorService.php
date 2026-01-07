<?php

namespace App\Services;

use Exception;

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

    /**
     * Get chat message limit for a plan
     */
    public function getChatLimit(string $planTier): int
    {
        return self::CHAT_LIMITS[$planTier] ?? 0;
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
            'cost_threshold' => $this->getThreshold($planTier),
            'model' => $this->getModelForPlan($planTier),
        ];
    }
}