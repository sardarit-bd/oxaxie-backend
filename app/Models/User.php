<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'account_status',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
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
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

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

    public function currentUsage(): HasOne
    {
        return $this->hasOne(UsageTracking::class)
            ->where('billing_cycle_date', today()->format('Y-m-d'));
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

    // public function notifications(): HasMany
    // {
    //     return $this->hasMany(Notification::class);
    // }

    // public function analyticsEvents(): HasMany
    // {
    //     return $this->hasMany(AnalyticsEvent::class);
    // }

    // public function auditLogs(): HasMany
    // {
    //     return $this->hasMany(AuditLog::class);
    // }

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
        return $this->subscription?->status === 'active';
    }


    // jwt auth
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
