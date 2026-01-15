<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\SubscriptionAiModelAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class SubscriptionAiModelAccessSeeder extends Seeder
{
    public function run(): void
    {
        Log::info('Seeding Subscription AI Model Access...');

        // Define which models are available for which plans
        $accessRules = [
            'free' => [
                'gemini-1.5-flash' => ['allowed' => true, 'priority' => 10],
                'gemini-2.0-flash-exp' => ['allowed' => true, 'priority' => 9],
                'claude-3-5-haiku-20241022' => ['allowed' => true, 'priority' => 8],
            ],
            'pro' => [
                'gemini-1.5-flash' => ['allowed' => true, 'priority' => 10],
                'gemini-2.0-flash-exp' => ['allowed' => true, 'priority' => 9],
                'gemini-2.5-flash' => ['allowed' => true, 'priority' => 8],
                'claude-3-5-haiku-20241022' => ['allowed' => true, 'priority' => 7],
                'claude-3-5-sonnet-20241022' => ['allowed' => true, 'priority' => 6],
            ],
            'pro_plus' => [
                'gemini-1.5-flash' => ['allowed' => true, 'priority' => 9],
                'gemini-1.5-pro' => ['allowed' => true, 'priority' => 10],
                'gemini-2.0-flash-exp' => ['allowed' => true, 'priority' => 8],
                'gemini-2.5-flash' => ['allowed' => true, 'priority' => 7],
                'claude-3-5-haiku-20241022' => ['allowed' => true, 'priority' => 6],
                'claude-3-5-sonnet-20241022' => ['allowed' => true, 'priority' => 5],
            ],
            'enterprise' => [
                'gemini-1.5-flash' => ['allowed' => true, 'priority' => 8],
                'gemini-1.5-pro' => ['allowed' => true, 'priority' => 9],
                'gemini-2.0-flash-exp' => ['allowed' => true, 'priority' => 7],
                'gemini-2.5-flash' => ['allowed' => true, 'priority' => 6],
                'claude-3-5-haiku-20241022' => ['allowed' => true, 'priority' => 5],
                'claude-3-5-sonnet-20241022' => ['allowed' => true, 'priority' => 4],
                'claude-3-opus-20240229' => ['allowed' => true, 'priority' => 10], // Best for enterprise
            ],
        ];

        foreach ($accessRules as $planTier => $models) {
            foreach ($models as $modelName => $config) {
                $model = AiModel::where('name', $modelName)->first();

                if (!$model) {
                    Log::warning("Model {$modelName} not found, skipping access rule");
                    continue;
                }

                SubscriptionAiModelAccess::updateOrCreate(
                    [
                        'subscription_plan_tier' => $planTier,
                        'ai_model_id' => $model->id,
                    ],
                    [
                        'is_allowed' => $config['allowed'],
                        'priority' => $config['priority'],
                    ]
                );
            }

            Log::info("Access rules configured for {$planTier} plan");
        }

        Log::info('All subscription AI model access rules seeded successfully');
    }
}