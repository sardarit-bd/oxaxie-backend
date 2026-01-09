<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCaseOutcomeRequest;
use App\Http\Requests\UpdateCaseOutcomeRequest;
use App\Services\CaseOutcomeService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CaseOutcomeController extends Controller
{
    use ApiResponse;

    public function __construct(
        private CaseOutcomeService $outcomeService
    ) {}

    /**
     * Store a newly created outcome.
     */
    public function store(StoreCaseOutcomeRequest $request): JsonResponse
    {
        Log::info('=== Case Outcome Store Request ===');
        Log::info('Validated data:', $request->validated());

        try {
            $user = $request->user();
            $outcome = $this->outcomeService->createOutcome(
                $request->validated(),
                $user->id
            );

            return $this->successResponse(
                ['outcome' => $outcome],
                'Case outcome submitted successfully',
                201
            );

        } catch (Exception $e) {
            Log::error('Case outcome creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);

            $statusCode = match($e->getMessage()) {
                'Case not found or access denied' => 404,
                'Outcome already exists for this case' => 409,
                'Case must be marked as resolved before submitting outcome' => 400,
                default => 500
            };

            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    /**
     * Display the specified outcome.
     */
    public function show(Request $request, string $caseId): JsonResponse
    {
        Log::info('=== Case Outcome Show Request ===');
        Log::info('Case ID:', ['case_id' => $caseId]);

        try {
            $user = $request->user();
            
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }
            
            $outcome = $this->outcomeService->getOutcomeByCaseId($caseId, $user->id);

            if (!$outcome) {
                return $this->successResponse(
                    ['outcome' => null],
                    'No outcome found for this case'
                );
            }

            return $this->successResponse(
                ['outcome' => $outcome],
                'Outcome retrieved successfully'
            );

        } catch (Exception $e) {
            Log::error('Failed to retrieve outcome', [
                'error' => $e->getMessage(),
                'case_id' => $caseId
            ]);

            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update the specified outcome.
     */
    public function update(UpdateCaseOutcomeRequest $request, string $outcomeId): JsonResponse
    {
        Log::info('=== Case Outcome Update Request ===');
        Log::info('Outcome ID:', ['outcome_id' => $outcomeId]);
        Log::info('Validated data:', $request->validated());

        try {
            $user = $request->user();
            $outcome = $this->outcomeService->updateOutcome(
                $outcomeId,
                $request->validated(),
                $user->id
            );

            return $this->successResponse(
                ['outcome' => $outcome],
                'Outcome updated successfully'
            );

        } catch (Exception $e) {
            Log::error('Outcome update failed', [
                'error' => $e->getMessage(),
                'outcome_id' => $outcomeId
            ]);

            $statusCode = match($e->getMessage()) {
                'Outcome not found' => 404,
                'Access denied' => 403,
                default => 500
            };

            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    /**
     * Remove the specified outcome.
     */
    public function destroy(Request $request, string $outcomeId): JsonResponse
    {
        try {
            $user = $request->user();
            $this->outcomeService->deleteOutcome($outcomeId, $user->id);

            return $this->successResponse(
                null,
                'Outcome deleted successfully'
            );

        } catch (Exception $e) {
            Log::error('Outcome deletion failed', [
                'error' => $e->getMessage(),
                'outcome_id' => $outcomeId
            ]);

            $statusCode = match($e->getMessage()) {
                'Outcome not found' => 404,
                'Access denied' => 403,
                default => 500
            };

            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    /**
     * Get user's outcomes.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $outcomes = $this->outcomeService->getUserOutcomes($user->id);

            return $this->successResponse(
                ['outcomes' => $outcomes],
                'Outcomes retrieved successfully'
            );

        } catch (Exception $e) {
            Log::error('Failed to retrieve outcomes', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id
            ]);

            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get published testimonials (public endpoint).
     */
    public function testimonials(Request $request): JsonResponse
    {
        try {
            $limit = $request->query('limit', 10);
            $testimonials = $this->outcomeService->getPublishedTestimonials($limit);

            return $this->successResponse(
                ['testimonials' => $testimonials],
                'Testimonials retrieved successfully'
            );

        } catch (Exception $e) {
            Log::error('Failed to retrieve testimonials', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get outcome statistics (admin).
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $statistics = $this->outcomeService->getStatistics();

            return $this->successResponse(
                ['statistics' => $statistics],
                'Statistics retrieved successfully'
            );

        } catch (Exception $e) {
            Log::error('Failed to retrieve statistics', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}