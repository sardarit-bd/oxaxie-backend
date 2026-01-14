<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AiProvider extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'adapter_type',
        'custom_adapter_class',
        'endpoint_config',
        'auth_config',
        'request_transformer',
        'response_parser',
        'description',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'endpoint_config' => 'array',
        'auth_config' => 'array',
        'request_transformer' => 'array',
        'response_parser' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    // Relationships
    public function credentials(): HasMany
    {
        return $this->hasMany(AiProviderCredential::class);
    }

    public function models(): HasMany
    {
        return $this->hasMany(AiModel::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helpers
    public function getActiveCredential(?int $userId = null)
    {
        return $this->credentials()
            ->where('is_active', true)
            ->where('user_id', $userId)
            ->first();
    }

    public function hasActiveCredential(?int $userId = null): bool
    {
        return $this->getActiveCredential($userId) !== null;
    }
}