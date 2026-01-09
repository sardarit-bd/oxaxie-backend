<?php

namespace App\Services;

use App\Models\AllCase;
use App\Models\CaseOutcome;
use App\Repositories\CaseOutcomeRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CaseOutcomeService
{
    public function __construct(
        private CaseOutcomeRepository $outcomeRepository
    ) {}

    /**
     * Create a case outcome.
     */
    public function createOutcome(array $data, string $userId): CaseOutcome
    {
        DB::beginTransaction();

        try {
            $case = AllCase::where('id', $data['all_case_id'])
                ->where('user_id', $userId)
                ->first();

            if (!$case) {
                throw new Exception('Case not found or access denied');
            }

            $existingOutcome = $this->outcomeRepository->findByCaseId($data['all_case_id']);
            if ($existingOutcome) {
                throw new Exception('Outcome already exists for this case');
            }

            if ($case->status !== 'resolved') {
                throw new Exception('Case must be marked as resolved before submitting outcome');
            }

            $data['user_id'] = $userId;

            $data['money_saved'] = $data['money_saved'] ?? null;
            $data['money_recovered'] = $data['money_recovered'] ?? null;
            $data['court_avoided'] = $data['court_avoided'] ?? false;
            $data['hired_attorney'] = $data['hired_attorney'] ?? false;
            $data['ai_helpfulness_rating'] = $data['ai_helpfulness_rating'] ?? null;
            $data['feedback_text'] = $data['feedback_text'] ?? null;
            $data['would_recommend'] = $data['would_recommend'] ?? null;
            $data['testimonial_consent'] = $data['testimonial_consent'] ?? false;
            $data['days_to_resolution'] = $data['days_to_resolution'] ?? $this->calculateDaysToResolution($case);

            $outcome = $this->outcomeRepository->create($data);

            DB::commit();

            Log::info('Case outcome created', [
                'outcome_id' => $outcome->id,
                'case_id' => $data['all_case_id'],
                'user_id' => $userId,
                'outcome_type' => $data['outcome_type']
            ]);

            return $outcome;

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create case outcome', [
                'error' => $e->getMessage(),
                'case_id' => $data['all_case_id'] ?? null,
                'user_id' => $userId
            ]);

            throw $e;
        }
    }

    /**
     * Update a case outcome.
     */
    public function updateOutcome(string $outcomeId, array $data, string $userId): CaseOutcome
    {
        DB::beginTransaction();

        try {
            $outcome = $this->outcomeRepository->findById($outcomeId);

            if (!$outcome) {
                throw new Exception('Outcome not found');
            }

            if ($outcome->user_id !== $userId) {
                throw new Exception('Access denied');
            }

            $this->outcomeRepository->update($outcome, $data);

            DB::commit();

            Log::info('Case outcome updated', [
                'outcome_id' => $outcomeId,
                'user_id' => $userId
            ]);

            return $outcome->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update case outcome', [
                'error' => $e->getMessage(),
                'outcome_id' => $outcomeId,
                'user_id' => $userId
            ]);

            throw $e;
        }
    }

    /**
     * Get outcome by case ID.
     */
    public function getOutcomeByCaseId(string $caseId, string $userId): ?CaseOutcome
    {
        $outcome = $this->outcomeRepository->findByCaseId($caseId);

        if ($outcome && $outcome->user_id !== $userId) {
            throw new Exception('Access denied');
        }

        return $outcome;
    }

    /**
     * Delete outcome.
     */
    public function deleteOutcome(string $outcomeId, string $userId): bool
    {
        $outcome = $this->outcomeRepository->findById($outcomeId);

        if (!$outcome) {
            throw new Exception('Outcome not found');
        }

        if ($outcome->user_id !== $userId) {
            throw new Exception('Access denied');
        }

        return $this->outcomeRepository->delete($outcome);
    }

    /**
     * Get user's outcomes.
     */
    public function getUserOutcomes(string $userId)
    {
        return $this->outcomeRepository->getUserOutcomes($userId);
    }

    /**
     * Get published testimonials.
     */
    public function getPublishedTestimonials(int $limit = 10)
    {
        return $this->outcomeRepository->getPublishedTestimonials($limit);
    }

    /**
     * Get outcome statistics.
     */
    public function getStatistics(): array
    {
        return $this->outcomeRepository->getStatistics();
    }

    /**
     * Calculate days to resolution.
     */
    private function calculateDaysToResolution(AllCase $case): int
    {
        $createdDate = $case->created_at;
        $now = now();
        
        return $createdDate->diffInDays($now);
    }
}