<?php

namespace App\Repositories;

use App\Models\Subscription;
use Illuminate\Support\Str;

class SubscriptionRepository
{
    /**
     * Find subscription by user ID
     */
    public function findByUserId(string $userId): ?Subscription
    {
        return Subscription::where('user_id', $userId)->first();
    }

    /**
     * Find subscription by Stripe subscription ID
     */
    public function findByStripeSubscriptionId(string $stripeSubscriptionId): ?Subscription
    {
        return Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();
    }

    /**
     * Find subscription by user ID or Stripe subscription ID
     */
    public function findByUserOrStripeId(string $userId, string $stripeSubscriptionId): ?Subscription
    {
        return Subscription::where('user_id', $userId)
            ->orWhere('stripe_subscription_id', $stripeSubscriptionId)
            ->first();
    }

    /**
     * Create a new subscription
     */
    public function create(array $data): Subscription
    {
        $data['id'] = (string) Str::uuid();
        return Subscription::create($data);
    }

    /**
     * Update subscription
     */
    public function update(Subscription $subscription, array $data): bool
    {
        return $subscription->update($data);
    }

    /**
     * Delete subscription
     */
    public function delete(Subscription $subscription): bool
    {
        return $subscription->delete();
    }

    /**
     * Get active subscription by user ID
     */
    public function getActiveByUserId(string $userId): ?Subscription
    {
        return Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Cancel subscription
     */
    /**
     * Cancel subscription
     */
    public function cancel(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return $subscription;
    }
}