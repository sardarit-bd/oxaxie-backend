<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\UsageTracking;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\AiCostCalculatorService;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected AiCostCalculatorService $costCalculator
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


    public function downgrade(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            Log::info('Downgrade request', ['user_id' => $user->id]);
            
            DB::beginTransaction();
            
            // Get current subscription
            $subscription = $user->subscription;
            
            if (!$subscription || $subscription->plan_tier === 'free') {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already on the Free plan'
                ], 400);
            }
            
            $oldPlan = $subscription->plan_tier;
            
            // Get free plan limits using the cost calculator service
            $freeChatLimit = $this->costCalculator->getChatLimit('free');
            $freeCaseLimit = $this->costCalculator->getCaseLimit('free');
            $freeDocumentLimit = $this->costCalculator->getDocumentLimit('free');
            
            // Update subscription to free
            $subscription->update([
                'plan_tier' => 'free',
                'status' => 'active',
                'monthly_price' => 0.00,
                'stripe_subscription_id' => null,
                'stripe_customer_id' => null,
                'current_period_start' => now(),
                'current_period_end' => null,
                'cancelled_at' => now(),
            ]);
            
            // Get current usage tracking record
            $currentUsage = $user->currentUsage;
            
            // If no current usage, get the latest one
            if (!$currentUsage) {
                $currentUsage = UsageTracking::where('user_id', $user->id)
                    ->latest('created_at')
                    ->first();
            }
            
            // Cap current usage if it exceeds free limits
            if ($currentUsage) {
                $updates = [];
                
                // Cap messages if exceeded
                if ($currentUsage->messages_used > $freeChatLimit) {
                    $updates['messages_used'] = $freeChatLimit;
                    Log::info('Capping messages_used', [
                        'user_id' => $user->id,
                        'old_value' => $currentUsage->messages_used,
                        'new_value' => $freeChatLimit
                    ]);
                }
                
                // Reset cost threshold for free tier
                if ($currentUsage->cost_threshold_reached) {
                    $updates['cost_threshold_reached'] = false;
                    $updates['threshold_reached_at'] = null;
                    $updates['ai_cost_accumulated'] = 0.00;
                }
                
                // Update if there are any changes
                if (!empty($updates)) {
                    $currentUsage->update($updates);
                }
                
                // Log if cases exceed free limit (but don't delete them)
                if ($currentUsage->cases_created > $freeCaseLimit) {
                    Log::info('User has exceeded free case limit after downgrade', [
                        'user_id' => $user->id,
                        'cases_created' => $currentUsage->cases_created,
                        'free_limit' => $freeCaseLimit,
                        'note' => 'User can view existing cases but cannot create new ones'
                    ]);
                }
                
                // Log if documents exceed free limit
                if ($currentUsage->documents_generated > $freeDocumentLimit) {
                    Log::info('User has exceeded free document limit after downgrade', [
                        'user_id' => $user->id,
                        'documents_generated' => $currentUsage->documents_generated,
                        'free_limit' => $freeDocumentLimit,
                        'note' => 'User can view existing documents but cannot generate new ones'
                    ]);
                }
            }
            
            DB::commit();
            
            Log::info('Downgrade successful', [
                'user_id' => $user->id,
                'old_plan' => $oldPlan,
                'new_plan' => 'free',
                'new_limits' => [
                    'chat_limit' => $freeChatLimit,
                    'case_limit' => $freeCaseLimit,
                    'document_limit' => $freeDocumentLimit,
                ]
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully downgraded to Free plan',
                'data' => [
                    'subscription' => [
                        'plan_tier' => 'free',
                        'status' => 'active',
                        'monthly_price' => 0.00,
                    ],
                    'limits' => [
                        'messages_limit' => $freeChatLimit,
                        'cases_limit' => $freeCaseLimit,
                        'documents_limit' => $freeDocumentLimit,
                    ],
                    'current_usage' => [
                        'messages_used' => $currentUsage?->messages_used ?? 0,
                        'cases_created' => $currentUsage?->cases_created ?? 0,
                        'documents_generated' => $currentUsage?->documents_generated ?? 0,
                    ]
                ]
            ]);
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Downgrade failed', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to downgrade subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}