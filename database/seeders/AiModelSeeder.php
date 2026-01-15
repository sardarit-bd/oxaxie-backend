<?php

namespace Database\Seeders;

use App\Models\AiProvider;
use App\Models\AiModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiModelSeeder extends Seeder
{
    public function run(): void
    {
        Log::info('Seeding AI Models...');

        $gemini = AiProvider::where('slug', 'gemini')->first();
        $anthropic = AiProvider::where('slug', 'anthropic')->first();

        if (!$gemini || !$anthropic) {
            Log::error('Providers not found. Run AiProviderSeeder first.');
            return;
        }

        $geminiModels = [
            [
                'name' => 'gemini-1.5-flash',
                'display_name' => 'Gemini 1.5 Flash',
                'description' => 'Fast and efficient model for everyday tasks',
                'capabilities' => [
                    'text' => true,
                    'vision' => true,
                    'function_calling' => false,
                    'streaming' => false,
                ],
                'max_tokens' => 8192,
                'context_window' => 1000000,
                'priority' => 3,
            ],
            [
                'name' => 'gemini-1.5-pro',
                'display_name' => 'Gemini 1.5 Pro',
                'description' => 'Advanced model with enhanced capabilities',
                'capabilities' => [
                    'text' => true,
                    'vision' => true,
                    'function_calling' => true,
                    'streaming' => false,
                ],
                'max_tokens' => 8192,
                'context_window' => 2000000,
                'priority' => 2,
            ],
            [
                'name' => 'gemini-2.0-flash-exp',
                'display_name' => 'Gemini 2.0 Flash (Experimental)',
                'description' => 'Latest experimental model with free usage',
                'capabilities' => [
                    'text' => true,
                    'vision' => true,
                    'function_calling' => true,
                    'streaming' => false,
                ],
                'max_tokens' => 8192,
                'context_window' => 1000000,
                'priority' => 1,
            ],
            [
                'name' => 'gemini-2.5-flash',
                'display_name' => 'Gemini 2.5 Flash',
                'description' => 'Latest fast model with improved performance',
                'capabilities' => [
                    'text' => true,
                    'vision' => true,
                    'function_calling' => true,
                    'streaming' => false,
                ],
                'max_tokens' => 8192,
                'context_window' => 1000000,
                'priority' => 4,
            ],
        ];

        foreach ($geminiModels as $modelData) {
            AiModel::updateOrCreate(
                [
                    'ai_provider_id' => $gemini->id,
                    'name' => $modelData['name'],
                ],
                [
                    'display_name' => $modelData['display_name'],
                    'slug' => Str::slug($modelData['name']),
                    'description' => $modelData['description'],
                    'capabilities' => $modelData['capabilities'],
                    'max_tokens' => $modelData['max_tokens'],
                    'context_window' => $modelData['context_window'],
                    'is_active' => true,
                    'priority' => $modelData['priority'],
                ]
            );
        }

        Log::info('✓ Gemini models seeded');

        $anthropicModels = [
            [
                'name' => 'claude-3-5-haiku-20241022',
                'display_name' => 'Claude 3.5 Haiku',
                'description' => 'Fastest and most compact model',
                'capabilities' => [
                    'text' => true,
                    'vision' => true,
                    'function_calling' => false,
                    'streaming' => false,
                ],
                'max_tokens' => 4096,
                'context_window' => 200000,
                'priority' => 3,
            ],
            [
                'name' => 'claude-3-5-sonnet-20241022',
                'display_name' => 'Claude 3.5 Sonnet',
                'description' => 'Balance of intelligence and speed',
                'capabilities' => [
                    'text' => true,
                    'vision' => true,
                    'function_calling' => true,
                    'streaming' => false,
                ],
                'max_tokens' => 8192,
                'context_window' => 200000,
                'priority' => 2,
            ],
            [
                'name' => 'claude-3-opus-20240229',
                'display_name' => 'Claude 3 Opus',
                'description' => 'Most powerful model for complex tasks',
                'capabilities' => [
                    'text' => true,
                    'vision' => true,
                    'function_calling' => true,
                    'streaming' => false,
                ],
                'max_tokens' => 4096,
                'context_window' => 200000,
                'priority' => 1,
            ],
        ];

        foreach ($anthropicModels as $modelData) {
            AiModel::updateOrCreate(
                [
                    'ai_provider_id' => $anthropic->id,
                    'name' => $modelData['name'],
                ],
                [
                    'display_name' => $modelData['display_name'],
                    'slug' => Str::slug($modelData['name']),
                    'description' => $modelData['description'],
                    'capabilities' => $modelData['capabilities'],
                    'max_tokens' => $modelData['max_tokens'],
                    'context_window' => $modelData['context_window'],
                    'is_active' => true,
                    'priority' => $modelData['priority'],
                ]
            );
        }

        Log::info('Anthropic models seeded');
        Log::info('All AI models seeded successfully');
    }
}