<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditPurchase extends Model
{
     use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'amount',
        'credits_added',
        'status',
        'stripe_payment_intent_id',
        'expires_at',
        'applied_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'credits_added' => 'decimal:4',
        'expires_at' => 'datetime',
        'applied_at' => 'datetime',
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
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isApplied(): bool
    {
        return $this->applied_at !== null;
    }
}
