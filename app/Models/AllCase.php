<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AllCase extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'cases';

    protected $fillable = [
        'user_id',
        'issue_type',
        'location_city',
        'location_state',
        'location_country',
        'situation_description',
        'status',
        'resolution_type',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'case_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'case_id');
    }

    public function responseFeedback(): HasMany
    {
        return $this->hasMany(ResponseFeedback::class, 'case_id');
    }

    public function outcome(): HasOne
    {
        return $this->hasOne(CaseOutcome::class, 'case_id');
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    public function getDaysOpen(): int
    {
        $endDate = $this->resolved_at ?? now();
        return $this->created_at->diffInDays($endDate);
    }

    public function getLocationString(): string
    {
        $parts = array_filter([
            $this->location_city,
            $this->location_state,
            $this->location_country,
        ]);
        
        return implode(', ', $parts);
    }
}
