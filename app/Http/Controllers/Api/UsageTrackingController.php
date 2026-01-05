<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\UsageTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

class UsageTrackingController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected UsageTrackingService $usageTrackingService
    ) {}

    /**
     * Record or update usage (ONE RECORD PER USER PER BILLING CYCLE)
     * This is the main method to track user's daily/monthly usage
     */
    public function recordUsage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'billing_cycle_date' => 'required|date',
            'messages_used' => 'nullable|integer|min:0',
            'documents_generated' => 'nullable|integer|min:0',
            'cases_created' => 'nullable|integer|min:0',
            'ai_cost_accumulated' => 'nullable|numeric|min:0',
            'input_tokens_used' => 'nullable|integer|min:0',
            'output_tokens_used' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation error',
                422,
                $validator->errors()
            );
        }

        try {
            $result = $this->usageTrackingService->recordUsage(
                $request->user()->id,
                $validator->validated()
            );

            $message = $result['was_created'] 
                ? 'Usage tracking created successfully' 
                : 'Usage tracking updated successfully';

            return $this->successResponse(
                $result['usage_tracking'],
                $message
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Increment usage counters (for real-time tracking)
     * This is called each time user performs an action
     */
    public function incrementUsage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'billing_cycle_date' => 'nullable|date',
            'messages_used' => 'nullable|integer|min:1',
            'documents_generated' => 'nullable|integer|min:1',
            'cases_created' => 'nullable|integer|min:1',
            'ai_cost_accumulated' => 'nullable|numeric|min:0',
            'input_tokens_used' => 'nullable|integer|min:1',
            'output_tokens_used' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation error',
                422,
                $validator->errors()
            );
        }

        try {
            $usageTracking = $this->usageTrackingService->incrementUsage(
                $request->user()->id,
                $validator->validated()
            );

            return $this->successResponse(
                $usageTracking,
                'Usage incremented successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get current billing cycle usage for authenticated user
     */
    public function getCurrentUsage(Request $request): JsonResponse
    {
        try {
            $currentUsage = $this->usageTrackingService->getCurrentUsage(
                $request->user()->id
            );

            return $this->successResponse(
                $currentUsage,
                'Current usage retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get usage history for authenticated user
     */
    public function getUsageHistory(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation error',
                422,
                $validator->errors()
            );
        }

        try {
            $usageHistory = $this->usageTrackingService->getUsageHistory(
                $request->user()->id,
                $request->start_date,
                $request->end_date,
                $request->limit ?? 30
            );

            return $this->successResponse(
                $usageHistory,
                'Usage history retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get usage summary/statistics
     */
    public function getUsageSummary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation error',
                422,
                $validator->errors()
            );
        }

        try {
            $summary = $this->usageTrackingService->getUsageSummary(
                $request->user()->id,
                $request->start_date,
                $request->end_date
            );

            return $this->successResponse(
                $summary,
                'Usage summary retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Check if cost threshold reached and update if necessary
     */
    public function checkCostThreshold(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'billing_cycle_date' => 'required|date',
            'threshold_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation error',
                422,
                $validator->errors()
            );
        }

        try {
            $result = $this->usageTrackingService->checkCostThreshold(
                $request->user()->id,
                $request->billing_cycle_date,
                $request->threshold_amount
            );

            $message = $result['threshold_reached'] 
                ? 'Cost threshold has been reached' 
                : 'Cost threshold not reached yet';

            return $this->successResponse(
                $result['usage_tracking'],
                $message
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Get specific usage tracking record
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $usageTracking = $this->usageTrackingService->getUsageById(
                $request->user()->id,
                $id
            );

            return $this->successResponse(
                $usageTracking,
                'Usage tracking retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }
}