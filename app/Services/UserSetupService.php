<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\SubscriptionRepository;
use App\Repositories\CreditPurchaseRepository;
use App\Repositories\UsageTrackingRepository;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class UserSetupService
{
    public function __construct(
        protected SubscriptionRepository $subscriptionRepository,
        protected CreditPurchaseRepository $creditPurchaseRepository,
        protected UsageTrackingRepository $usageTrackingRepository
    ) {}

    /**
     * Setup new user with free tier subscription, initial credits, and usage tracking
     * This runs automatically after user registration
     */
    public function setupNewUser(User $user): array
    {
        try {
            DB::beginTransaction();

            $subscription = $this->createFreeSubscription($user);
            $creditPurchase = $this->createInitialCredits($user, $subscription);
            $usageTracking = $this->createInitialUsageTracking($user, $subscription);

            DB::commit();

            return [
                'subscription' => $subscription,
                'credit_purchase' => $creditPurchase,
                'usage_tracking' => $usageTracking,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Failed to setup user account: ' . $e->getMessage());
        }
    }

    /**
     * Create free tier subscription for new user
     */
    protected function createFreeSubscription(User $user)
    {
        $subscriptionData = [
            'user_id' => $user->id,
            'plan_tier' => 'free',
            'status' => 'active',
            'monthly_price' => 0.00,
            'current_period_start' => now(),
            'current_period_end' => now()->addYears(99),
            'stripe_subscription_id' => null,
            'stripe_customer_id' => null,
            'cancelled_at' => null,
        ];

        return $this->subscriptionRepository->create($subscriptionData);
    }

    /**
     * Create initial credit purchase
     */
    protected function createInitialCredits(User $user, $subscription)
    {
        $creditData = [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => 0.00,
            'credits_added' => 10.0000,
            'status' => 'completed',
            'stripe_payment_intent_id' => null,
            'expires_at' => now()->addYears(99),
            'applied_at' => now(),
        ];

        return $this->creditPurchaseRepository->create($creditData);
    }

    /**
     * Create initial usage tracking record with zeros
     */
    protected function createInitialUsageTracking(User $user, $subscription)
    {
        $usageData = [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'billing_cycle_date' => Carbon::today()->toDateString(),
            'messages_used' => 0,
            'documents_generated' => 0,
            'cases_created' => 0,
            'ai_cost_accumulated' => 0.0000,
            'input_tokens_used' => 0,
            'output_tokens_used' => 0,
            'cost_threshold_reached' => false,
            'threshold_reached_at' => null,
        ];

        return $this->usageTrackingRepository->updateOrCreate(
            $user->id,
            Carbon::today()->toDateString(),
            $usageData
        );
    }
}