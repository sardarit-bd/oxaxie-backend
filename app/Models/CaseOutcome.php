<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CaseOutcome extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'all_case_id',
        'user_id',
        'outcome_type',
        'outcome_summary',
        'money_saved',
        'money_recovered',
        'court_avoided',
        'hired_attorney',
        'ai_helpfulness_rating',
        'feedback_text',
        'would_recommend',
        'testimonial_consent',
        'testimonial_published',
        'days_to_resolution',
    ];

    protected $casts = [
        'court_avoided' => 'boolean',
        'hired_attorney' => 'boolean',
        'ai_helpfulness_rating' => 'integer',
        'would_recommend' => 'boolean',
        'testimonial_consent' => 'boolean',
        'testimonial_published' => 'boolean',
        'days_to_resolution' => 'integer',
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
    public function scopePublishedTestimonials($query)
    {
        return $query->where('testimonial_consent', true)
                    ->where('testimonial_published', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('outcome_type', $type);
    }


    public function scopeWithMinRating($query, int $rating)
    {
        return $query->where('ai_helpfulness_rating', '>=', $rating);
    }

    public function isWon(): bool
    {
        return $this->outcome_type === 'won';
    }

    public function isSettled(): bool
    {
        return $this->outcome_type === 'settled';
    }

    public function isLost(): bool
    {
        return $this->outcome_type === 'lost';
    }

    public function isDropped(): bool
    {
        return $this->outcome_type === 'dropped';
    }

    public function getTotalMonetaryBenefit(): float
    {
        return ($this->money_saved ?? 0) + ($this->money_recovered ?? 0);
    }

    public function isPositiveOutcome(): bool
    {
        return in_array($this->outcome_type, ['won', 'settled']);
    }

    public function canBeUsedAsTestimonial(): bool
    {
        return $this->testimonial_consent 
            && !$this->testimonial_published 
            && $this->isPositiveOutcome();
    }
}
