<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'account_status',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    
    /**
     * Get the user's active subscription
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->latest('current_period_start');
    }

    /**
     * Get all subscriptions (including inactive)
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function cases(): HasMany
    {
        return $this->hasMany(AllCase::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function usageTracking(): HasMany
    {
        return $this->hasMany(UsageTracking::class);
    }

    /**
     * Get current usage for the active subscription's billing cycle
     */
    public function currentUsage(): HasOne
    {
        return $this->hasOne(UsageTracking::class)
            ->whereHas('subscription', function ($query) {
                $query->where('status', 'active');
            })
            ->where(function ($query) {
                // Get the active subscription's billing cycle date
                $activeSubscription = $this->subscription;
                if ($activeSubscription && $activeSubscription->current_period_start) {
                    $billingCycleDate = Carbon::parse($activeSubscription->current_period_start)->toDateString();
                    $query->where('billing_cycle_date', $billingCycleDate);
                } else {
                    // Fallback to today if no active subscription
                    $query->where('billing_cycle_date', today()->format('Y-m-d'));
                }
            })
            ->latest('created_at');
    }

    public function creditPurchases(): HasMany
    {
        return $this->hasMany(CreditPurchase::class);
    }

    public function responseFeedback(): HasMany
    {
        return $this->hasMany(ResponseFeedback::class);
    }

    public function caseOutcomes(): HasMany
    {
        return $this->hasMany(CaseOutcome::class);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->account_status === 'active';
    }

    public function getPlanTier(): string
    {
        return $this->subscription?->plan_tier ?? 'free';
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription !== null && $this->subscription->status === 'active';
    }

    // JWT auth
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}