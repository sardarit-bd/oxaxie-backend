<?php

namespace App\Repositories;

use App\Models\CaseOutcome;
use Illuminate\Support\Str;

class CaseOutcomeRepository
{
    /**
     * Create a new case outcome.
     */
    public function create(array $data): CaseOutcome
    {
        $data['id'] = Str::uuid();
        
        return CaseOutcome::create($data);
    }

    /**
     * Find outcome by case ID.
     */
    public function findByCaseId(string $caseId): ?CaseOutcome
    {
        return CaseOutcome::where('all_case_id', $caseId)->first();
    }

    /**
     * Find outcome by ID.
     */
    public function findById(string $id): ?CaseOutcome
    {
        return CaseOutcome::find($id);
    }

    /**
     * Update an outcome.
     */
    public function update(CaseOutcome $outcome, array $data): bool
    {
        return $outcome->update($data);
    }

    /**
     * Delete an outcome.
     */
    public function delete(CaseOutcome $outcome): bool
    {
        return $outcome->delete();
    }

    /**
     * Get outcomes for a specific user.
     */
    public function getUserOutcomes(string $userId)
    {
        return CaseOutcome::where('user_id', $userId)
            ->with('case')
            ->latest()
            ->get();
    }

    /**
     * Get published testimonials.
     */
    public function getPublishedTestimonials(int $limit = 10)
    {
        return CaseOutcome::publishedTestimonials()
            ->with(['case' => function ($query) {
                $query->select('id', 'issue_type', 'location_city', 'location_state');
            }])
            ->select('id', 'all_case_id', 'outcome_type', 'outcome_summary', 'days_to_resolution', 'ai_helpfulness_rating', 'created_at')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get outcome statistics.
     */
    public function getStatistics(): array
    {
        return [
            'total_outcomes' => CaseOutcome::count(),
            'by_type' => CaseOutcome::selectRaw('outcome_type, COUNT(*) as count')
                ->groupBy('outcome_type')
                ->pluck('count', 'outcome_type')
                ->toArray(),
            'court_avoided_count' => CaseOutcome::where('court_avoided', true)->count(),
            'average_days_to_resolution' => CaseOutcome::avg('days_to_resolution'),
            'average_rating' => CaseOutcome::avg('ai_helpfulness_rating'),
            'would_recommend_count' => CaseOutcome::where('would_recommend', true)->count(),
        ];
    }
}