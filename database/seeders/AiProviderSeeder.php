<?php

namespace Database\Seeders;

use App\Models\AiProvider;
use App\Models\AiProviderCredential;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class AiProviderSeeder extends Seeder
{
    public function run(): void
    {
        Log::info('Seeding AI Providers...');

        $gemini = AiProvider::updateOrCreate(
            ['slug' => 'gemini'],
            [
                'name' => 'Google Gemini',
                'adapter_type' => 'generic_rest',
                'custom_adapter_class' => null,
                'endpoint_config' => [
                    'base_url' => 'https://generativelanguage.googleapis.com',
                    'api_version' => 'v1',
                    'endpoints' => [
                        'chat' => '/models/{model}:generateContent',
                        'vision' => '/v1beta/models/{model}:generateContent',
                    ],
                ],
                'auth_config' => [
                    'type' => 'query_param',
                    'key_name' => 'key',
                    'header_prefix' => null,
                ],
                'request_transformer' => [
                    'message_format' => 'gemini',
                    'system_prompt_location' => 'in_messages',
                    'role_mapping' => [
                        'user' => 'user',
                        'assistant' => 'model',
                        'system' => 'user',
                    ],
                ],
                'response_parser' => [
                    'content_path' => 'candidates.0.content.parts.0.text',
                    'input_tokens_path' => 'usageMetadata.promptTokenCount',
                    'output_tokens_path' => 'usageMetadata.candidatesTokenCount',
                ],
                'description' => 'Google Gemini AI models with vision and multimodal capabilities',
                'is_active' => true,
                'priority' => 1,
            ]
        );

        if ($apiKey = config('services.gemini.api_key')) {
            AiProviderCredential::updateOrCreate(
                [
                    'ai_provider_id' => $gemini->id,
                    'user_id' => null,
                ],
                [
                    'api_key' => $apiKey,
                    'is_active' => true,
                    'additional_config' => null,
                ]
            );
            Log::info('Gemini API key configured');
        } else {
            Log::warning('Gemini API key not found in .env');
        }

        $anthropic = AiProvider::updateOrCreate(
            ['slug' => 'anthropic'],
            [
                'name' => 'Anthropic Claude',
                'adapter_type' => 'generic_rest',
                'custom_adapter_class' => null,
                'endpoint_config' => [
                    'base_url' => 'https://api.anthropic.com',
                    'api_version' => 'v1',
                    'endpoints' => [
                        'chat' => '/messages',
                        'vision' => '/messages',
                    ],
                    'additional_headers' => [
                        'anthropic-version' => '2023-06-01',
                    ],
                ],
                'auth_config' => [
                    'type' => 'header',
                    'key_name' => 'x-api-key',
                    'header_prefix' => null,
                ],
                'request_transformer' => [
                    'message_format' => 'anthropic',
                    'system_prompt_location' => 'separate',
                    'role_mapping' => [
                        'user' => 'user',
                        'assistant' => 'assistant',
                    ],
                    'required_fields' => [
                        'max_tokens' => 4096,
                    ],
                ],
                'response_parser' => [
                    'content_path' => 'content.0.text',
                    'input_tokens_path' => 'usage.input_tokens',
                    'output_tokens_path' => 'usage.output_tokens',
                ],
                'description' => 'Anthropic Claude models with advanced reasoning capabilities',
                'is_active' => true,
                'priority' => 2,
            ]
        );
        if ($apiKey = config('services.anthropic.api_key')) {
            AiProviderCredential::updateOrCreate(
                [
                    'ai_provider_id' => $anthropic->id,
                    'user_id' => null,
                ],
                [
                    'api_key' => $apiKey,
                    'is_active' => true,
                    'additional_config' => null,
                ]
            );
            Log::info('Anthropic API key configured');
        } else {
            Log::warning('Anthropic API key not found in .env');
        }

        Log::info('AI Providers seeded successfully');
    }
}