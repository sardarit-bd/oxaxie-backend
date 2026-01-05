<?php

namespace App\Services;

use App\Repositories\SubscriptionRepository;
use App\Models\Subscription;
use Exception;

class SubscriptionService
{
    public function __construct(
        protected SubscriptionRepository $subscriptionRepository
    ) {}

    /**
     * Store or update a user's subscription
     * This follows the pattern: One subscription per user
     */
    public function storeOrUpdate(string $userId, array $data): array
    {
        // Check if subscription exists by user or stripe ID
        $subscription = $this->subscriptionRepository->findByUserOrStripeId(
            $userId,
            $data['stripe_subscription_id']
        );

        $wasRecentlyCreated = false;

        if ($subscription) {
            // Update existing subscription
            $this->subscriptionRepository->update($subscription, $data);
            $subscription = $subscription->fresh();
            $message = 'Subscription updated successfully';
        } else {
            // Create new subscription
            $subscriptionData = array_merge($data, [
                'user_id' => $userId,
            ]);
            
            $subscription = $this->subscriptionRepository->create($subscriptionData);
            $wasRecentlyCreated = true;
            $message = 'Subscription created successfully';
        }

        return [
            'subscription' => $subscription,
            'message' => $message,
            'was_created' => $wasRecentlyCreated,
        ];
    }

    /**
     * Get user's subscription
     */
    public function getUserSubscription(string $userId): ?Subscription
    {
        return $this->subscriptionRepository->findByUserId($userId);
    }

    /**
     * Get active subscription for user
     */
    public function getActiveSubscription(string $userId): ?Subscription
    {
        $subscription = $this->subscriptionRepository->getActiveByUserId($userId);
        
        if (!$subscription) {
            throw new Exception('No active subscription found');
        }

        return $subscription;
    }

    /**
     * Update subscription
     */
    public function updateSubscription(string $userId, array $data): Subscription
    {
        $subscription = $this->subscriptionRepository->findByUserId($userId);
        
        if (!$subscription) {
            throw new Exception('Subscription not found');
        }

        $this->subscriptionRepository->update($subscription, $data);
        
        return $subscription->fresh();
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(string $userId): Subscription
    {
        $subscription = $this->subscriptionRepository->findByUserId($userId);
        
        if (!$subscription) {
            throw new Exception('Subscription not found');
        }

        $this->subscriptionRepository->cancel($subscription);
        
        return $subscription->fresh();
    }

    /**
     * Delete subscription
     */
    public function deleteSubscription(string $userId): bool
    {
        $subscription = $this->subscriptionRepository->findByUserId($userId);
        
        if (!$subscription) {
            throw new Exception('Subscription not found');
        }

        return $this->subscriptionRepository->delete($subscription);
    }

    /**
     * Check if user has active subscription
     */
    public function hasActiveSubscription(string $userId): bool
    {
        $subscription = $this->subscriptionRepository->getActiveByUserId($userId);
        return $subscription !== null;
    }

    /**
     * Get subscription by Stripe subscription ID
     */
    public function getByStripeId(string $stripeSubscriptionId): ?Subscription
    {
        return $this->subscriptionRepository->findByStripeSubscriptionId($stripeSubscriptionId);
    }
}