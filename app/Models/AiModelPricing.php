<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiModelPricing extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_model_id',
        'subscription_plan_tier',
        'input_cost_per_1m_tokens',
        'output_cost_per_1m_tokens',
        'effective_from',
        'effective_until',
        'is_active',
    ];

    protected $casts = [
        'input_cost_per_1m_tokens' => 'decimal:6',
        'output_cost_per_1m_tokens' => 'decimal:6',
        'effective_from' => 'datetime',
        'effective_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('effective_from')
              ->orWhere('effective_from', '<=', now());
        })->where(function ($q) {
            $q->whereNull('effective_until')
              ->orWhere('effective_until', '>=', now());
        });
    }

    public function scopeForPlan($query, ?string $planTier)
    {
        return $query->where('subscription_plan_tier', $planTier);
    }

    // Helpers
    public function calculateCost(int $inputTokens, int $outputTokens): float
    {
        $inputCost = ($inputTokens / 1_000_000) * $this->input_cost_per_1m_tokens;
        $outputCost = ($outputTokens / 1_000_000) * $this->output_cost_per_1m_tokens;
        
        return round($inputCost + $outputCost, 6);
    }
}