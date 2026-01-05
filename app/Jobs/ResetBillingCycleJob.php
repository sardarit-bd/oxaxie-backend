<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Repositories\UsageTrackingRepository;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Reset Billing Cycle Job
 * 
 * Runs daily to check for expired billing cycles and reset usage
 * Schedule this job in app/Console/Kernel.php:
 * 
 * $schedule->job(new ResetBillingCycleJob)->daily();
 */
class ResetBillingCycleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected ?UsageTrackingRepository $usageTrackingRepository = null
    ) {
        $this->usageTrackingRepository = $usageTrackingRepository ?? app(UsageTrackingRepository::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting billing cycle reset job');

        $today = Carbon::today();
        
        // Get all active subscriptions
        $subscriptions = Subscription::where('status', 'active')
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', $today)
            ->get();

        $resetCount = 0;

        foreach ($subscriptions as $subscription) {
            try {
                // Calculate new billing period
                $currentPeriodStart = $today;
                $currentPeriodEnd = $today->copy()->addMonth();

                // Update subscription period
                $subscription->update([
                    'current_period_start' => $currentPeriodStart,
                    'current_period_end' => $currentPeriodEnd,
                ]);

                // Create new usage tracking record for new billing cycle
                $this->usageTrackingRepository->updateOrCreate(
                    $subscription->user_id,
                    $today->toDateString(),
                    [
                        'subscription_id' => $subscription->id,
                        'messages_used' => 0,
                        'documents_generated' => 0,
                        'cases_created' => 0,
                        'ai_cost_accumulated' => 0.0000,
                        'input_tokens_used' => 0,
                        'output_tokens_used' => 0,
                        'cost_threshold_reached' => false,
                        'threshold_reached_at' => null,
                    ]
                );

                $resetCount++;

                Log::info('Billing cycle reset for user', [
                    'user_id' => $subscription->user_id,
                    'plan_tier' => $subscription->plan_tier,
                    'new_period_start' => $currentPeriodStart,
                    'new_period_end' => $currentPeriodEnd,
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to reset billing cycle', [
                    'user_id' => $subscription->user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Billing cycle reset job completed', [
            'subscriptions_processed' => $subscriptions->count(),
            'successful_resets' => $resetCount,
        ]);
    }
}