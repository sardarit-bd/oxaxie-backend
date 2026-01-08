<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\ResponseFeedbackService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class ResponseFeedbackController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ResponseFeedbackService $feedbackService
    ) {}

    /**
     * Create response feedback for a case
     * POST /api/cases/{caseId}/feedback
     */
    public function store(Request $request, string $caseId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'response_type' => 'required|in:complied,partial_compliance,refused,no_response,counter_offer',
            'response_description' => 'required|string|min:10',
            'response_date' => 'required|date|before_or_equal:today',
            'action_taken_date' => 'nullable|date|before_or_equal:response_date',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation error',
                422,
                $validator->errors()
            );
        }

        try {
            $feedback = $this->feedbackService->createFeedback(
                $request->user()->id,
                $caseId,
                $validator->validated()
            );

            Log::info('Response feedback created', [
                'feedback_id' => $feedback->id,
                'case_id' => $caseId,
                'user_id' => $request->user()->id
            ]);

            return $this->successResponse(
                $feedback->load('documents'),
                'Response feedback created successfully',
                201
            );
        } catch (Exception $e) {
            Log::error('Failed to create response feedback', [
                'case_id' => $caseId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'Failed to create response feedback',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get all feedback for a case
     * GET /api/cases/{caseId}/feedback
     */
    public function index(Request $request, string $caseId): JsonResponse
    {
        try {
            $feedbacks = $this->feedbackService->getFeedbackForCase(
                $request->user()->id,
                $caseId
            );

            // Get statistics
            $statistics = $this->feedbackService->getCaseStatistics(
                $request->user()->id,
                $caseId
            );

            return $this->successResponse(
                [
                    'feedbacks' => $feedbacks,
                    'statistics' => $statistics,
                ],
                'Feedback retrieved successfully'
            );
        } catch (Exception $e) {
            Log::error('Failed to fetch feedback', [
                'case_id' => $caseId,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'Failed to fetch feedback',
                500
            );
        }
    }

    /**
     * Get single feedback
     * GET /api/feedback/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $feedback = $this->feedbackService->getFeedback(
                $request->user()->id,
                $id
            );

            return $this->successResponse(
                $feedback->load(['case', 'documents']),
                'Feedback retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                404
            );
        }
    }

    /**
     * Update feedback
     * PUT /api/feedback/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'response_type' => 'sometimes|in:complied,partial_compliance,refused,no_response,counter_offer',
            'response_description' => 'sometimes|string|min:10',
            'response_date' => 'sometimes|date|before_or_equal:today',
            'action_taken_date' => 'nullable|date|before_or_equal:response_date',
            'status' => 'sometimes|in:active,resolved,escalated,closed',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation error',
                422,
                $validator->errors()
            );
        }

        try {
            $feedback = $this->feedbackService->updateFeedback(
                $request->user()->id,
                $id,
                $validator->validated()
            );

            Log::info('Response feedback updated', [
                'feedback_id' => $id,
                'user_id' => $request->user()->id
            ]);

            return $this->successResponse(
                $feedback->load('documents'),
                'Feedback updated successfully'
            );
        } catch (Exception $e) {
            Log::error('Failed to update feedback', [
                'feedback_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                $e->getMessage(),
                $e->getMessage() === 'Response feedback not found' ? 404 : 500
            );
        }
    }

    /**
     * Delete feedback
     * DELETE /api/feedback/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $this->feedbackService->deleteFeedback(
                $request->user()->id,
                $id
            );

            Log::info('Response feedback deleted', [
                'feedback_id' => $id,
                'user_id' => $request->user()->id
            ]);

            return $this->successResponse(
                null,
                'Feedback deleted successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                404
            );
        }
    }

    /**
     * Analyze feedback with AI
     * POST /api/feedback/{id}/analyze
     */
    public function analyzeWithAI(Request $request, string $id): JsonResponse
    {
        try {
            Log::info('Starting AI analysis', [
                'feedback_id' => $id,
                'user_id' => $request->user()->id
            ]);

            $feedback = $this->feedbackService->analyzeWithAI(
                $request->user()->id,
                $id
            );

            Log::info('AI analysis completed', [
                'feedback_id' => $id,
                'urgency_level' => $feedback->urgency_level
            ]);

            return $this->successResponse(
                [
                    'feedback' => $feedback->load('documents'),
                    'analysis' => [
                        'ai_analysis' => $feedback->ai_analysis,
                        'ai_next_steps' => $feedback->ai_next_steps,
                        'escalation_options' => $feedback->escalation_options,
                        'urgency_level' => $feedback->urgency_level,
                        'recommended_deadline' => $feedback->recommended_deadline?->format('Y-m-d'),
                        'is_urgent' => $feedback->isUrgent(),
                        'has_passed_deadline' => $feedback->hasPassedDeadline(),
                    ]
                ],
                'AI analysis completed successfully'
            );
        } catch (Exception $e) {
            Log::error('AI analysis failed', [
                'feedback_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to analyze feedback with AI',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get feedback statistics for a case
     * GET /api/cases/{caseId}/feedback/statistics
     */
    public function statistics(Request $request, string $caseId): JsonResponse
    {
        try {
            $statistics = $this->feedbackService->getCaseStatistics(
                $request->user()->id,
                $caseId
            );

            return $this->successResponse(
                $statistics,
                'Statistics retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                'Failed to fetch statistics',
                500
            );
        }
    }
}