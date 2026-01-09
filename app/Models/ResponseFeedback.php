<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ResponseFeedback extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

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
        'ai_next_steps',
        'escalation_options',
        'urgency_level',
        'recommended_deadline',
        'status',
        'sent_to_chat',
    ];

    protected $casts = [
        'response_date' => 'date',
        'action_taken_date' => 'date',
        'recommended_deadline' => 'date',
        'days_to_response' => 'integer',
        'ai_analyzed' => 'boolean',
        'escalation_options' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'sent_to_chat' => 'boolean',
    ];


    public function case(): BelongsTo
    {
        return $this->belongsTo(AllCase::class, 'all_case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function caseDocuments(): HasMany
    {
        return $this->hasMany(CaseDocument::class, 'response_feedback_id');
    }
    
    public function documents(): HasMany
    {
        return $this->caseDocuments();
    }

    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class);
    }

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

    public function isUrgent(): bool
    {
        return in_array($this->urgency_level, ['high', 'critical']);
    }

    public function hasPassedDeadline(): bool
    {
        return $this->recommended_deadline && 
               $this->recommended_deadline->isPast();
    }
}