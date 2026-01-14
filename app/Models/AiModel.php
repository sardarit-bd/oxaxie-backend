<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ai_provider_id',
        'name',
        'display_name',
        'slug',
        'description',
        'capabilities',
        'max_tokens',
        'context_window',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'max_tokens' => 'integer',
        'context_window' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    // Relationships
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }

    public function pricing(): HasMany
    {
        return $this->hasMany(AiModelPricing::class);
    }

    public function subscriptionAccess(): HasMany
    {
        return $this->hasMany(SubscriptionAiModelAccess::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithCapability($query, string $capability)
    {
        return $query->whereJsonContains('capabilities->' . $capability, true);
    }

    // Helpers
    public function hasCapability(string $capability): bool
    {
        return ($this->capabilities[$capability] ?? false) === true;
    }

    public function getPricingForPlan(?string $planTier = null): ?AiModelPricing
    {
        return $this->pricing()
            ->where('is_active', true)
            ->where('subscription_plan_tier', $planTier)
            ->where(function ($query) {
                $query->whereNull('effective_from')
                      ->orWhere('effective_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('effective_until')
                      ->orWhere('effective_until', '>=', now());
            })
            ->first();
    }

    public function isAllowedForPlan(string $planTier): bool
    {
        return $this->subscriptionAccess()
            ->where('subscription_plan_tier', $planTier)
            ->where('is_allowed', true)
            ->exists();
    }
}