<?php

namespace App\Repositories;

use App\Models\ResponseFeedback;
use Illuminate\Database\Eloquent\Collection;

class ResponseFeedbackRepository
{
    /**
     * Create new response feedback
     */
    public function create(array $data): ResponseFeedback
    {
        return ResponseFeedback::create($data);
    }

    /**
     * Find by ID
     */
    public function findById(string $id): ?ResponseFeedback
    {
        return ResponseFeedback::with(['case', 'documents'])->find($id);
    }

    /**
     * Find by ID for specific user
     */
    public function findByIdAndUser(string $id, string $userId): ?ResponseFeedback
    {
        return ResponseFeedback::with(['case', 'documents'])
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Get all feedback for a case
     */
    public function findByCaseId(string $caseId, string $userId): Collection
    {
        return ResponseFeedback::with(['documents'])
            ->where('all_case_id', $caseId)
            ->where('user_id', $userId)
            ->orderBy('response_date', 'desc')
            ->get();
    }

    /**
     * Update response feedback
     */
    public function update(ResponseFeedback $feedback, array $data): bool
    {
        return $feedback->update($data);
    }

    /**
     * Delete response feedback
     */
    public function delete(ResponseFeedback $feedback): bool
    {
        return $feedback->delete();
    }

    /**
     * Mark as analyzed with AI data
     */
    public function markAsAnalyzed(ResponseFeedback $feedback, array $aiData): bool
    {
        return $feedback->update([
            'ai_analyzed' => true,
            'ai_analysis' => $aiData['analysis'] ?? null,
            'ai_next_steps' => $aiData['next_steps'] ?? null,
            'escalation_options' => $aiData['escalation_options'] ?? null,
            'urgency_level' => $aiData['urgency_level'] ?? 'medium',
            'recommended_deadline' => $aiData['recommended_deadline'] ?? null,
        ]);
    }

    /**
     * Get feedback statistics for a case
     */
    public function getCaseStatistics(string $caseId, string $userId): array
    {
        $feedbacks = ResponseFeedback::where('all_case_id', $caseId)
            ->where('user_id', $userId)
            ->get();

        return [
            'total_responses' => $feedbacks->count(),
            'complied' => $feedbacks->where('response_type', 'complied')->count(),
            'refused' => $feedbacks->where('response_type', 'refused')->count(),
            'no_response' => $feedbacks->where('response_type', 'no_response')->count(),
            'average_response_time' => $feedbacks->avg('days_to_response'),
            'urgent_count' => $feedbacks->whereIn('urgency_level', ['high', 'critical'])->count(),
        ];
    }
}