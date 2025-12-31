<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ResponseFeedback extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'response_feedback';

    protected $fillable = [
        'all_case_id',
        'user_id',
        'response_type',
        'response_description',
        'response_date',
        'action_taken_date',
        'days_to_response',
        'ai_analyzed',
        'ai_analysis',
    ];

    protected $casts = [
        'response_date' => 'date',
        'action_taken_date' => 'date',
        'days_to_response' => 'integer',
        'ai_analyzed' => 'boolean',
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
    public function calculateDaysToResponse(): void
    {
        if ($this->action_taken_date && $this->response_date) {
            $this->days_to_response = $this->action_taken_date->diffInDays($this->response_date);
            $this->save();
        }
    }

    public function isComplied(): bool
    {
        return $this->response_type === 'complied';
    }

    public function isPartialCompliance(): bool
    {
        return $this->response_type === 'partial_compliance';
    }

    public function isRefused(): bool
    {
        return $this->response_type === 'refused';
    }

    public function isNoResponse(): bool
    {
        return $this->response_type === 'no_response';
    }

    public function isCounterOffer(): bool
    {
        return $this->response_type === 'counter_offer';
    }
}
