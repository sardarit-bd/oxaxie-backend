<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\AiModelPricing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class AiModelPricingSeeder extends Seeder
{
    public function run(): void
    {
        Log::info('Seeding AI Model Pricing...');

        // Pricing data: [model_name => [plan_tier => [input, output]]]
        $pricingData = [
            // Gemini Models
            'gemini-1.5-flash' => [
                null => [0.075, 0.30],
                'free' => [0.075, 0.30],
                'pro' => [0.075, 0.30],
                'pro_plus' => [0.075, 0.30],
                'enterprise' => [0.075, 0.30],
            ],
            'gemini-1.5-pro' => [
                null => [1.25, 5.00],
                'free' => [1.25, 5.00],
                'pro' => [1.25, 5.00],
                'pro_plus' => [1.25, 5.00],
                'enterprise' => [1.25, 5.00],
            ],
            'gemini-2.0-flash-exp' => [
                null => [0.00, 0.00],
                'free' => [0.00, 0.00],
                'pro' => [0.00, 0.00],
                'pro_plus' => [0.00, 0.00],
                'enterprise' => [0.00, 0.00],
            ],
            'gemini-2.5-flash' => [
                null => [0.075, 0.30],
                'free' => [0.075, 0.30],
                'pro' => [0.075, 0.30],
                'pro_plus' => [0.075, 0.30],
                'enterprise' => [0.075, 0.30],
            ],

            // Anthropic (Claude) Models
            'claude-3-5-haiku-20241022' => [
                null => [0.80, 4.00],
                'free' => [0.80, 4.00],
                'pro' => [0.80, 4.00],
                'pro_plus' => [0.80, 4.00],
                'enterprise' => [0.80, 4.00],
            ],
            'claude-3-5-sonnet-20241022' => [
                null => [3.00, 15.00],
                'free' => [3.00, 15.00],
                'pro' => [3.00, 15.00],
                'pro_plus' => [3.00, 15.00],
                'enterprise' => [3.00, 15.00],
            ],
            'claude-3-opus-20240229' => [
                null => [15.00, 75.00],
                'free' => [15.00, 75.00],
                'pro' => [15.00, 75.00],
                'pro_plus' => [15.00, 75.00],
                'enterprise' => [15.00, 75.00],
            ],
        ];

        foreach ($pricingData as $modelName => $plans) {
            $model = AiModel::where('name', $modelName)->first();

            if (!$model) {
                Log::warning("Model {$modelName} not found, skipping pricing");
                continue;
            }

            foreach ($plans as $planTier => $costs) {
                [$inputCost, $outputCost] = $costs;

                AiModelPricing::updateOrCreate(
                    [
                        'ai_model_id' => $model->id,
                        'subscription_plan_tier' => $planTier,
                    ],
                    [
                        'input_cost_per_1m_tokens' => $inputCost,
                        'output_cost_per_1m_tokens' => $outputCost,
                        'effective_from' => now(),
                        'effective_until' => null,
                        'is_active' => true,
                    ]
                );
            }

            Log::info("Pricing configured for {$modelName}");
        }

        Log::info('All AI model pricing seeded successfully');
    }
}