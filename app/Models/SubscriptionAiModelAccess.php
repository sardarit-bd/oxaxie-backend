<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubscriptionAiModelAccess extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_plan_tier',
        'ai_model_id',
        'is_allowed',
        'priority',
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
        'priority' => 'integer',
    ];

    // Relationships
    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }

    // Scopes
    public function scopeAllowed($query)
    {
        return $query->where('is_allowed', true);
    }

    public function scopeForPlan($query, string $planTier)
    {
        return $query->where('subscription_plan_tier', $planTier);
    }
}