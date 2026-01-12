<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Repositories\SubscriptionRepository;
use App\Repositories\UsageTrackingRepository;

class SubscriptionService
{
    public function __construct(
        protected SubscriptionRepository $subscriptionRepository,
        protected UsageTrackingRepository $usageTrackingRepository
    ) {}

    /**
     * Store or update a user's subscription with fresh start logic
     * When a new subscription is purchased:
     * - Cancels old active subscription
     * - Creates new subscription
     * - Resets usage tracking (creates new record for new billing cycle)
     */
    public function storeOrUpdate(string $userId, array $data): array
    {
        DB::beginTransaction();

        try {
            Log::info('=== Starting storeOrUpdate ===', [
                'user_id' => $userId,
                'plan_tier' => $data['plan_tier'] ?? 'unknown',
                'stripe_subscription_id' => $data['stripe_subscription_id'] ?? 'unknown'
            ]);

            $oldSubscription = $this->subscriptionRepository->getActiveByUserId($userId);
            
            $wasRecentlyCreated = false;
            $oldSubscriptionCancelled = false;

            // Check if updating existing subscription
            $existingStripeSubscription = null;
            if (isset($data['stripe_subscription_id'])) {
                $existingStripeSubscription = Subscription::where('stripe_subscription_id', $data['stripe_subscription_id'])->first();
            }

            if ($existingStripeSubscription) {
                Log::info('Updating existing subscription', [
                    'user_id' => $userId,
                    'subscription_id' => $existingStripeSubscription->id,
                    'stripe_subscription_id' => $data['stripe_subscription_id']
                ]);

                $existingStripeSubscription->update($data);
                $subscription = $existingStripeSubscription->fresh();
                $message = 'Subscription updated successfully';
                
                DB::commit();
                
                return [
                    'subscription' => $subscription,
                    'message' => $message,
                    'was_created' => false,
                    'old_subscription_cancelled' => false,
                ];
            }

            // Cancel old subscription if different Stripe ID
            if ($oldSubscription && $oldSubscription->stripe_subscription_id !== $data['stripe_subscription_id']) {
                Log::info('Cancelling old subscription for fresh start', [
                    'user_id' => $userId,
                    'old_subscription_id' => $oldSubscription->id,
                    'old_plan_tier' => $oldSubscription->plan_tier,
                    'new_plan_tier' => $data['plan_tier']
                ]);

                $oldSubscription->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
                $oldSubscriptionCancelled = true;

                Log::info('Old subscription cancelled', [
                    'old_subscription_id' => $oldSubscription->id,
                    'cancelled_at' => $oldSubscription->cancelled_at
                ]);
            }

            // Create new subscription
            $subscriptionData = array_merge($data, [
                'user_id' => $userId,
                'id' => \Illuminate\Support\Str::uuid(),
            ]);
            
            Log::info('Creating new subscription', [
                'user_id' => $userId,
                'plan_tier' => $data['plan_tier'],
                'stripe_subscription_id' => $data['stripe_subscription_id']
            ]);

            $subscription = Subscription::create($subscriptionData);
            $wasRecentlyCreated = true;

            Log::info('New subscription created', ['subscription_id' => $subscription->id]);

   
            try {
                $billingCycleDate = Carbon::parse($subscription->current_period_start)->toDateString();
                
                Log::info('Attempting to create/update usage tracking', [
                    'user_id' => $userId,
                    'subscription_id' => $subscription->id,
                    'billing_cycle_date' => $billingCycleDate,
                    'new_plan_tier' => $data['plan_tier'],
                    'old_plan_tier' => $oldSubscription?->plan_tier ?? 'none'
                ]);

                $existingUsage = \App\Models\UsageTracking::where('user_id', $userId)
                    ->where('billing_cycle_date', $billingCycleDate)
                    ->first();
                
                $shouldResetUsage = $this->shouldResetUsageOnRenewal(
                    $oldSubscription?->plan_tier ?? 'none',
                    $data['plan_tier']
                );

                if ($existingUsage) {
                    Log::info('Updating existing usage tracking', [
                        'usage_id' => $existingUsage->id,
                        'should_reset' => $shouldResetUsage
                    ]);

                    if ($shouldResetUsage) {
                    
                        $existingUsage->update([
                            'subscription_id' => $subscription->id,
                            'messages_used' => 0,
                            'documents_generated' => 0,
                            'cases_created' => 0,
                            'ai_cost_accumulated' => 0.0000,
                            'input_tokens_used' => 0,
                            'output_tokens_used' => 0,
                            'cost_threshold_reached' => false,
                        ]);
                    } else {
                        // ✅ PRO/PRO_PLUS RENEWAL: Keep usage, only update subscription_id
                        $existingUsage->update([
                            'subscription_id' => $subscription->id,
                            // Usage data preserved
                        ]);
                    }
                } else {
                    // New usage record
                    if ($shouldResetUsage || !$oldSubscription) {
                        // ✅ FREE TIER or NEW USER: Create fresh usage
                        \App\Models\UsageTracking::create([
                            'id' => \Illuminate\Support\Str::uuid(),
                            'user_id' => $userId,
                            'subscription_id' => $subscription->id,
                            'billing_cycle_date' => $billingCycleDate,
                            'messages_used' => 0,
                            'documents_generated' => 0,
                            'cases_created' => 0,
                            'ai_cost_accumulated' => 0.0000,
                            'input_tokens_used' => 0,
                            'output_tokens_used' => 0,
                            'cost_threshold_reached' => false,
                        ]);
                    } else {
                        // ✅ PRO/PRO_PLUS RENEWAL: Copy previous usage to new period
                        $previousUsage = \App\Models\UsageTracking::where('user_id', $userId)
                            ->where('subscription_id', $oldSubscription->id)
                            ->orderBy('billing_cycle_date', 'desc')
                            ->first();

                        \App\Models\UsageTracking::create([
                            'id' => \Illuminate\Support\Str::uuid(),
                            'user_id' => $userId,
                            'subscription_id' => $subscription->id,
                            'billing_cycle_date' => $billingCycleDate,
                            'messages_used' => $previousUsage?->messages_used ?? 0,
                            'documents_generated' => $previousUsage?->documents_generated ?? 0,
                            'cases_created' => $previousUsage?->cases_created ?? 0,
                            'ai_cost_accumulated' => $previousUsage?->ai_cost_accumulated ?? 0.0000,
                            'input_tokens_used' => $previousUsage?->input_tokens_used ?? 0,
                            'output_tokens_used' => $previousUsage?->output_tokens_used ?? 0,
                            'cost_threshold_reached' => $previousUsage?->cost_threshold_reached ?? false,
                        ]);
                    }
                }

                Log::info('Usage tracking created/updated successfully');

            } catch (Exception $usageError) {
                Log::error('Failed to create/update usage tracking', [
                    'error' => $usageError->getMessage(),
                    'trace' => $usageError->getTraceAsString()
                ]);
            }

            $message = $oldSubscriptionCancelled 
                ? 'New subscription created successfully. Previous subscription has been cancelled.' 
                : 'Subscription created successfully';

            DB::commit();

            Log::info('=== Subscription creation completed successfully ===', [
                'user_id' => $userId,
                'new_subscription_id' => $subscription->id,
                'old_subscription_cancelled' => $oldSubscriptionCancelled
            ]);

            return [
                'subscription' => $subscription,
                'message' => $message,
                'was_created' => $wasRecentlyCreated,
                'old_subscription_cancelled' => $oldSubscriptionCancelled,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('=== Subscription store/update FAILED ===', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Determine if usage should reset
     * 
     * @param string $oldPlanTier Previous plan tier
     * @param string $newPlanTier New plan tier
     * @return bool True if usage should reset, false if preserved
     */
    protected function shouldResetUsageOnRenewal(string $oldPlanTier, string $newPlanTier): bool
    {
        if ($oldPlanTier === 'none' || $oldPlanTier === 'free') {
            return true;
        }
        
        if (in_array($oldPlanTier, ['pro', 'pro_plus']) && in_array($newPlanTier, ['pro', 'pro_plus'])) {
            return false;
        }

        if (in_array($oldPlanTier, ['pro', 'pro_plus']) && $newPlanTier === 'free') {
            return true;
        }

        return true;
    }

    public function getUserSubscription(string $userId): ?Subscription
    {
        return $this->subscriptionRepository->findByUserId($userId);
    }

    public function getActiveSubscription(string $userId): ?Subscription
    {
        $subscription = $this->subscriptionRepository->getActiveByUserId($userId);
        
        if (!$subscription) {
            throw new Exception('No active subscription found');
        }

        return $subscription;
    }

    public function updateSubscription(string $userId, array $data): Subscription
    {
        $subscription = $this->subscriptionRepository->findByUserId($userId);
        
        if (!$subscription) {
            throw new Exception('Subscription not found');
        }

        $this->subscriptionRepository->update($subscription, $data);
        
        return $subscription->fresh();
    }

    public function cancelSubscription(string $userId): Subscription
    {
        $subscription = $this->subscriptionRepository->findByUserId($userId);
        
        if (!$subscription) {
            throw new Exception('Subscription not found');
        }

        $this->subscriptionRepository->cancel($subscription);
        
        return $subscription->fresh();
    }

    public function deleteSubscription(string $userId): bool
    {
        $subscription = $this->subscriptionRepository->findByUserId($userId);
        
        if (!$subscription) {
            throw new Exception('Subscription not found');
        }

        return $this->subscriptionRepository->delete($subscription);
    }

    public function hasActiveSubscription(string $userId): bool
    {
        $subscription = $this->subscriptionRepository->getActiveByUserId($userId);
        return $subscription !== null;
    }

    public function getByStripeId(string $stripeSubscriptionId): ?Subscription
    {
        return $this->subscriptionRepository->findByStripeSubscriptionId($stripeSubscriptionId);
    }
}