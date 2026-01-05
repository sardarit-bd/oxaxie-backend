<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UsageTracking extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'usage_trackings';

    protected $fillable = [
        'user_id',
        'subscription_id',
        'billing_cycle_date',
        'messages_used',
        'documents_generated',
        'cases_created',
        'ai_cost_accumulated',
        'input_tokens_used',
        'output_tokens_used',
        'cost_threshold_reached',
        'threshold_reached_at',
    ];

    protected $casts = [
        'billing_cycle_date' => 'date',
        'messages_used' => 'integer',
        'documents_generated' => 'integer',
        'cases_created' => 'integer',
        'ai_cost_accumulated' => 'decimal:4',
        'input_tokens_used' => 'integer',
        'output_tokens_used' => 'integer',
        'cost_threshold_reached' => 'boolean',
        'threshold_reached_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    // Helper methods
    public function incrementMessage(int $inputTokens, int $outputTokens, float $cost): void
    {
        $this->increment('messages_used');
        $this->increment('input_tokens_used', $inputTokens);
        $this->increment('output_tokens_used', $outputTokens);
        $this->increment('ai_cost_accumulated', $cost);
    }

    public function incrementDocument(): void
    {
        $this->increment('documents_generated');
    }

    public function incrementCase(): void
    {
        $this->increment('cases_created');
    }

    public function hasReachedThreshold(): bool
    {
        return $this->cost_threshold_reached;
    }

    public function getRemainingCost(float $threshold): float
    {
        return max(0, $threshold - $this->ai_cost_accumulated);
    }
}
