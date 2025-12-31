<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FeatureFlag extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'flag_key',
        'flag_name',
        'description',
        'is_enabled',
        'enabled_for_users',
        'enabled_for_plans',
        'rollout_percentage',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'enabled_for_users' => 'array',
        'enabled_for_plans' => 'array',
        'rollout_percentage' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Helper methods
    public function isEnabledForUser(?User $user = null): bool
    {
        if (!$this->is_enabled) {
            return false;
        }

        // Check user-specific access
        if ($user && $this->enabled_for_users) {
            if (in_array($user->id, $this->enabled_for_users)) {
                return true;
            }
        }

        // Check plan-specific access
        if ($user && $this->enabled_for_plans) {
            $userPlan = $user->getPlanTier();
            if (in_array($userPlan, $this->enabled_for_plans)) {
                return true;
            }
        }

        // Check rollout percentage
        if ($this->rollout_percentage > 0 && $this->rollout_percentage < 100) {
            $hash = $user ? crc32($user->id . $this->flag_key) : rand(0, 99);
            return ($hash % 100) < $this->rollout_percentage;
        }

        return $this->rollout_percentage === 100;
    }

    public function enable(): void
    {
        $this->update(['is_enabled' => true]);
    }

    public function disable(): void
    {
        $this->update(['is_enabled' => false]);
    }
}
