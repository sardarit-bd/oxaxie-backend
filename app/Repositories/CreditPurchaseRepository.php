<?php

namespace App\Repositories;

use App\Models\CreditPurchase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CreditPurchaseRepository
{
    /**
     * Find credit purchase by ID for specific user
     */
    public function findByIdAndUser(string $id, string $userId): ?CreditPurchase
    {
        return CreditPurchase::where('id', $id)
            ->where('user_id', $userId)
            ->with('subscription:id,plan_tier,status')
            ->first();
    }

    /**
     * Find credit purchase by Stripe payment intent ID
     */
    public function findByPaymentIntentId(string $paymentIntentId): ?CreditPurchase
    {
        return CreditPurchase::where('stripe_payment_intent_id', $paymentIntentId)->first();
    }

    /**
     * Get all credit purchases for a user
     */
    public function getAllByUser(string $userId): Collection
    {
        return CreditPurchase::where('user_id', $userId)
            ->with('subscription:id,plan_tier,status')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get credit purchases by status for a user
     */
    public function getByUserAndStatus(string $userId, string $status): Collection
    {
        return CreditPurchase::where('user_id', $userId)
            ->where('status', $status)
            ->with('subscription:id,plan_tier,status')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get completed purchases summary for a user
     */
    public function getCompletedSummary(string $userId): array
    {
        $purchases = $this->getAllByUser($userId);

        return [
            'total_spent' => $purchases->where('status', 'completed')->sum('amount'),
            'total_credits_purchased' => $purchases->where('status', 'completed')->sum('credits_added'),
            'pending_purchases' => $purchases->where('status', 'pending')->count(),
            'completed_purchases' => $purchases->where('status', 'completed')->count(),
            'failed_purchases' => $purchases->where('status', 'failed')->count(),
            'refunded_purchases' => $purchases->where('status', 'refunded')->count(),
        ];
    }

    /**
     * Calculate available credits for a user
     */
    public function getAvailableCredits(string $userId): float
    {
        return (float) CreditPurchase::where('user_id', $userId)
            ->where('status', 'completed')
            ->where('expires_at', '>', now())
            ->sum('credits_added');
    }

    /**
     * Create a new credit purchase
     */
    public function create(array $data): CreditPurchase
    {
        $data['id'] = (string) Str::uuid();
        return CreditPurchase::create($data);
    }

    /**
     * Update credit purchase
     */
    public function update(CreditPurchase $creditPurchase, array $data): bool
    {
        return $creditPurchase->update($data);
    }

    /**
     * Delete credit purchase
     */
    public function delete(CreditPurchase $creditPurchase): bool
    {
        return $creditPurchase->delete();
    }
}