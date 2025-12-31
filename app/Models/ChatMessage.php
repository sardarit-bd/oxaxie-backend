<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatMessage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'all_case_id',
        'user_id',
        'role',
        'content',
        'ai_model_used',
        'input_tokens',
        'output_tokens',
        'cost',
        'metadata',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cost' => 'decimal:6',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function case(): BelongsTo
    {
        return $this->belongsTo(AllCase::class, 'all_case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function isUserMessage(): bool
    {
        return $this->role === 'user';
    }

    public function isAssistantMessage(): bool
    {
        return $this->role === 'assistant';
    }

    public function isSystemMessage(): bool
    {
        return $this->role === 'system';
    }

    public function getTotalTokens(): int
    {
        return ($this->input_tokens ?? 0) + ($this->output_tokens ?? 0);
    }
}
