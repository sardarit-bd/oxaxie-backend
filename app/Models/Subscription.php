<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'plan_tier',
        'status',
        'monthly_price',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'stripe_subscription_id',
        'stripe_customer_id',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usageTracking(): HasMany
    {
        return $this->hasMany(UsageTracking::class);
    }

    public function creditPurchases(): HasMany
    {
        return $this->hasMany(CreditPurchase::class);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function isFree(): bool
    {
        return $this->plan_tier === 'free';
    }

    public function isPro(): bool
    {
        return $this->plan_tier === 'pro';
    }

    public function isProPlus(): bool
    {
        return $this->plan_tier === 'pro_plus';
    }

    public function daysUntilRenewal(): int
    {
        return $this->current_period_end?->diffInDays(now()) ?? 0;
    }

    /**
     * Get usage limits for the current plan tier using cost calculator
     */
    public function getLimits(): array
    {
        $costCalculator = app(\App\Services\AiCostCalculatorService::class);
        
        return [
            'messages_limit' => $costCalculator->getChatLimit($this->plan_tier),
            'cases_limit' => $costCalculator->getCaseLimit($this->plan_tier),
            'documents_limit' => $costCalculator->getDocumentLimit($this->plan_tier),
            'threshold' => $costCalculator->getThreshold($this->plan_tier),
        ];
    }

    /**
     * Check if user can perform actions based on their plan
     */
    public function canCreateCase(): bool
    {
        $costCalculator = app(\App\Services\AiCostCalculatorService::class);
        $limit = $costCalculator->getCaseLimit($this->plan_tier);
        return $limit === null || $limit > 0;
    }

    public function canGenerateDocument(): bool
    {
        $costCalculator = app(\App\Services\AiCostCalculatorService::class);
        $limit = $costCalculator->getDocumentLimit($this->plan_tier);
        return $limit === null || $limit > 0;
    }

    public function canSendMessage(): bool
    {
        $costCalculator = app(\App\Services\AiCostCalculatorService::class);
        $limit = $costCalculator->getChatLimit($this->plan_tier);
        return $limit === null || $limit > 0;
    }
}