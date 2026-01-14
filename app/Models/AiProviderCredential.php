<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;

class AiProviderCredential extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ai_provider_id',
        'user_id',
        'api_key',
        'additional_config',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'additional_config' => 'array',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
    ];

    // Relationships
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Accessors & Mutators
    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = Crypt::encryptString($value);
    }

    public function getApiKeyAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    // Get masked API key for display
    public function getMaskedApiKey(): string
    {
        $key = $this->api_key;
        if (strlen($key) <= 8) {
            return str_repeat('*', strlen($key));
        }
        return str_repeat('*', strlen($key) - 4) . substr($key, -4);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeSystemWide($query)
    {
        return $query->whereNull('user_id');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}