<?php

namespace App\Services;

/**
 * AI Cost Calculator Service
 * Calculates token costs based on AI model and plan tier
 */
class AiCostCalculatorService
{
    /**
     * Model pricing per million tokens (in USD)
     * Source: Official pricing as of January 2025
     */
    private const PRICING = [
        'gemini-2.5-flash' => [
            'input' => 0.00,
            'output' => 0.00, 
        ],
        'claude-sonnet-4-20250514' => [
            'input' => 3.00,
            'output' => 15.00,
        ],
        'claude-opus-4-20250514' => [
            'input' => 15.00,
            'output' => 75.00,
        ],
    ];

    /**
     * Cost thresholds per plan tier (in USD)
     */
    private const THRESHOLDS = [
        'free' => 0.00,
        'pro' => 5.00,
        'pro_plus' => 19.00,
    ];

    /**
     * Chat limits per plan tier
     */
    private const CHAT_LIMITS = [
        'free' => 10,
        'pro' => null,
        'pro_plus' => null,
    ];

    /**
     * Case limits per plan tier per month
     */
    private const CASE_LIMITS = [
        'free' => 0,
        'pro' => 3,  
        'pro_plus' => null, 
    ];

    /**
     * Document limits per plan tier
     */
    private const DOCUMENT_LIMITS = [
        'free' => 1,      
        'pro' => null,      
        'pro_plus' => null,  
    ];

    /**
     * Get AI model based on plan tier
     */
    public function getModelForPlan(string $planTier): string
    {
        return match ($planTier) {
            'free' => 'gemini-2.5-flash',
            'pro' => 'claude-sonnet-4-20250514',
            'pro_plus' => 'claude-opus-4-20250514',
            default => 'gemini-2.5-flash',
        };
    }

    /**
     * Calculate cost for tokens
     * 
     * @param string $model AI model name
     * @param int $inputTokens Number of input tokens
     * @param int $outputTokens Number of output tokens
     * @return float Cost in USD
     */
    public function calculateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        if (!isset(self::PRICING[$model])) {
            return 0.0;
        }

        $pricing = self::PRICING[$model];
        
        // Calculate cost per million tokens
        $inputCost = ($inputTokens / 1_000_000) * $pricing['input'];
        $outputCost = ($outputTokens / 1_000_000) * $pricing['output'];
        
        return round($inputCost + $outputCost, 6);
    }

    /**
     * Get cost threshold for plan tier
     * 
     * @param string $planTier Plan tier (free, pro, pro_plus)
     * @return float Threshold in USD
     */
    public function getThreshold(string $planTier): float
    {
        return self::THRESHOLDS[$planTier] ?? 0.00;
    }

    /**
     * Check if cost threshold is reached
     * 
     * @param float $currentCost Current accumulated cost
     * @param string $planTier Plan tier
     * @return bool True if threshold reached
     */
    public function isThresholdReached(float $currentCost, string $planTier): bool
    {
        $threshold = $this->getThreshold($planTier);
        
        // Free tier has no cost threshold
        if ($planTier === 'free') {
            return false;
        }
        
        return $currentCost >= $threshold;
    }

    /**
     * Get remaining cost before threshold
     * 
     * @param float $currentCost Current accumulated cost
     * @param string $planTier Plan tier
     * @return float Remaining cost in USD
     */
    public function getRemainingCost(float $currentCost, string $planTier): float
    {
        $threshold = $this->getThreshold($planTier);
        $remaining = $threshold - $currentCost;
        
        return max(0, $remaining);
    }

    /**
     * Get chat limit for plan tier
     * 
     * @param string $planTier Plan tier
     * @return int|null Chat limit (null means unlimited until threshold)
     */
    public function getChatLimit(string $planTier): ?int
    {
        return self::CHAT_LIMITS[$planTier];
    }

    /**
     * Get case limit for plan tier
     * 
     * @param string $planTier Plan tier
     * @return int|null Case limit (null means unlimited)
     */
    public function getCaseLimit(string $planTier): ?int
    {
        return self::CASE_LIMITS[$planTier];
    }

    /**
     * Get document limit for plan tier
     * 
     * @param string $planTier Plan tier
     * @return int|null Document limit (null means until threshold)
     */
    public function getDocumentLimit(string $planTier): ?int
    {
        return self::DOCUMENT_LIMITS[$planTier];
    }

    /**
     * Estimate tokens from text (rough estimation)
     * Rule of thumb: 1 token ≈ 4 characters for English text
     * 
     * @param string $text Text to estimate
     * @return int Estimated token count
     */
    public function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Get plan details including limits and thresholds
     * 
     * @param string $planTier Plan tier
     * @return array Plan details
     */
    public function getPlanDetails(string $planTier): array
    {
        return [
            'plan_tier' => $planTier,
            'model' => $this->getModelForPlan($planTier),
            'chat_limit' => $this->getChatLimit($planTier),
            'case_limit' => $this->getCaseLimit($planTier),
            'document_limit' => $this->getDocumentLimit($planTier),
            'cost_threshold' => $this->getThreshold($planTier),
            'pricing' => self::PRICING[$this->getModelForPlan($planTier)] ?? null,
        ];
    }
}