<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

class SubscriptionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Store or update a user's subscription
     * Now with fresh start logic - cancels old subscription when new one is purchased
     */
    public function storeOrUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_tier' => 'required|in:free,pro,pro_plus',
            'status' => 'required|in:active,cancelled,expired,past_due',
            'monthly_price' => 'required|numeric|min:0',
            'current_period_start' => 'required|date',
            'current_period_end' => 'required|date|after_or_equal:current_period_start',
            'stripe_subscription_id' => 'required|string',
            'stripe_customer_id' => 'nullable|string',
            'cancelled_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation error',
                422,
                $validator->errors()
            );
        }

        try {
            $result = $this->subscriptionService->storeOrUpdate(
                $request->user()->id,
                $validator->validated()
            );

            $statusCode = $result['was_created'] ? 201 : 200;

            $responseData = [
                'subscription' => $result['subscription'],
                'old_subscription_cancelled' => $result['old_subscription_cancelled'] ?? false,
            ];

            return $this->successResponse(
                $responseData,
                $result['message'],
                $statusCode
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
    // public function storeOrUpdate(Request $request): JsonResponse
    // {
    //     $validator = Validator::make($request->all(), [
    //         'plan_tier' => 'required|in:free,pro,pro_plus',
    //         'status' => 'required|in:active,cancelled,expired,past_due',
    //         'monthly_price' => 'required|numeric|min:0',
    //         'current_period_start' => 'required|date',
    //         'current_period_end' => 'required|date|after_or_equal:current_period_start',
    //         'stripe_subscription_id' => 'required|string',
    //         'stripe_customer_id' => 'nullable|string',
    //         'cancelled_at' => 'nullable|date',
    //     ]);

    //     if ($validator->fails()) {
    //         return $this->errorResponse(
    //             'Validation error',
    //             422,
    //             $validator->errors()
    //         );
    //     }

    //     try {
    //         $result = $this->subscriptionService->storeOrUpdate(
    //             $request->user()->id,
    //             $validator->validated()
    //         );

    //         $statusCode = $result['was_created'] ? 201 : 200;

    //         return $this->successResponse(
    //             $result['subscription'],
    //             $result['message'],
    //             $statusCode
    //         );
    //     } catch (Exception $e) {
    //         return $this->errorResponse($e->getMessage(), 400);
    //     }
    // }

    /**
     * Get user's subscription
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->getUserSubscription(
                $request->user()->id
            );

            if (!$subscription) {
                return $this->errorResponse('Subscription not found', 404);
            }

            return $this->successResponse(
                $subscription,
                'Subscription retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get active subscription
     */
    public function active(Request $request): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->getActiveSubscription(
                $request->user()->id
            );

            return $this->successResponse(
                $subscription,
                'Active subscription retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Update subscription
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_tier' => 'sometimes|in:free,pro,pro_plus',
            'status' => 'sometimes|in:active,cancelled,expired,past_due',
            'monthly_price' => 'sometimes|numeric|min:0',
            'current_period_start' => 'sometimes|date',
            'current_period_end' => 'sometimes|date|after_or_equal:current_period_start',
            'stripe_subscription_id' => 'sometimes|string',
            'stripe_customer_id' => 'sometimes|nullable|string',
            'cancelled_at' => 'sometimes|nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation error',
                422,
                $validator->errors()
            );
        }

        try {
            $subscription = $this->subscriptionService->updateSubscription(
                $request->user()->id,
                $validator->validated()
            );

            return $this->successResponse(
                $subscription,
                'Subscription updated successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->cancelSubscription(
                $request->user()->id
            );

            return $this->successResponse(
                $subscription,
                'Subscription cancelled successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Delete subscription
     */
    public function destroy(Request $request): JsonResponse
    {
        try {
            $this->subscriptionService->deleteSubscription(
                $request->user()->id
            );

            return $this->successResponse(
                null,
                'Subscription deleted successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Check if user has active subscription
     */
    public function hasActive(Request $request): JsonResponse
    {
        try {
            $hasActive = $this->subscriptionService->hasActiveSubscription(
                $request->user()->id
            );

            return $this->successResponse(
                ['has_active_subscription' => $hasActive],
                'Subscription status checked successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}