<?php

namespace App\Repositories;

use App\Models\UsageTracking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class UsageTrackingRepository
{
    /**
     * Find usage tracking by ID for specific user
     */
    public function findByIdAndUser(string $id, string $userId): ?UsageTracking
    {
        return UsageTracking::where('id', $id)
            ->where('user_id', $userId)
            ->with('subscription:id,plan_tier,status')
            ->first();
    }

    /**
     * Find or create usage tracking for billing cycle
     */
    public function findOrCreateByBillingCycle(string $userId, string $billingCycleDate, ?string $subscriptionId = null): UsageTracking
    {
        return UsageTracking::firstOrCreate(
            [
                'user_id' => $userId,
                'billing_cycle_date' => $billingCycleDate,
            ],
            [
                'id' => (string) Str::uuid(),
                'subscription_id' => $subscriptionId,
                'messages_used' => 0,
                'documents_generated' => 0,
                'cases_created' => 0,
                'ai_cost_accumulated' => 0.0000,
                'input_tokens_used' => 0,
                'output_tokens_used' => 0,
            ]
        );
    }

    /**
     * Update or create usage tracking
     */
    public function updateOrCreate(string $userId, string $billingCycleDate, array $data): UsageTracking
    {
        return UsageTracking::updateOrCreate(
            [
                'user_id' => $userId,
                'billing_cycle_date' => $billingCycleDate,
            ],
            $data
        );
    }

    /**
     * Get current billing cycle usage
     */
    public function getCurrentUsage(string $userId, string $billingCycleDate): ?UsageTracking
    {
        return UsageTracking::where('user_id', $userId)
            ->where('billing_cycle_date', $billingCycleDate)
            ->with('subscription:id,plan_tier,status')
            ->first();
    }

    /**
     * Get usage history for a user
     */
    public function getHistory(string $userId, ?string $startDate = null, ?string $endDate = null, int $limit = 30): Collection
    {
        $query = UsageTracking::where('user_id', $userId)
            ->with('subscription:id,plan_tier,status');

        if ($startDate && $endDate) {
            $query->whereBetween('billing_cycle_date', [$startDate, $endDate]);
        }

        return $query->orderBy('billing_cycle_date', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get usage summary
     */
    public function getSummary(string $userId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = UsageTracking::where('user_id', $userId);

        if ($startDate && $endDate) {
            $query->whereBetween('billing_cycle_date', [$startDate, $endDate]);
        }

        $summary = $query->selectRaw('
                SUM(messages_used) as total_messages,
                SUM(documents_generated) as total_documents,
                SUM(cases_created) as total_cases,
                SUM(ai_cost_accumulated) as total_cost,
                SUM(input_tokens_used) as total_input_tokens,
                SUM(output_tokens_used) as total_output_tokens,
                AVG(messages_used) as avg_messages_per_day,
                COUNT(*) as total_days_tracked
            ')
            ->first();

        return [
            'total_messages' => (int) ($summary->total_messages ?? 0),
            'total_documents' => (int) ($summary->total_documents ?? 0),
            'total_cases' => (int) ($summary->total_cases ?? 0),
            'total_cost' => (float) ($summary->total_cost ?? 0),
            'total_input_tokens' => (int) ($summary->total_input_tokens ?? 0),
            'total_output_tokens' => (int) ($summary->total_output_tokens ?? 0),
            'avg_messages_per_day' => round($summary->avg_messages_per_day ?? 0, 2),
            'total_days_tracked' => (int) ($summary->total_days_tracked ?? 0),
        ];
    }

    /**
     * Increment usage counters
     */
    public function incrementCounters(UsageTracking $usageTracking, array $increments): void
    {
        foreach ($increments as $field => $value) {
            if ($value > 0) {
                $usageTracking->increment($field, $value);
            }
        }
    }

    /**
     * Update usage tracking
     */
    public function update(UsageTracking $usageTracking, array $data): bool
    {
        return $usageTracking->update($data);
    }

    /**
     * Delete usage tracking
     */
    public function delete(UsageTracking $usageTracking): bool
    {
        return $usageTracking->delete();
    }
}