<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AiChatService;
use App\Services\AiProviders\AiModelSelector;
use Illuminate\Console\Command;

class TestDynamicAiSystem extends Command
{
    protected $signature = 'test:ai-system {user_id}';
    protected $description = 'Test the dynamic AI system';

    public function handle(
        AiChatService $aiChatService,
        AiModelSelector $modelSelector
    ) {
        $userId = $this->argument('user_id');
        $user = User::find($userId);

        if (!$user) {
            $this->error("User not found with ID: {$userId}");
            return 1;
        }

        $this->info("Testing AI system for user: {$user->name}");
        $this->info("Plan: " . ($user->subscription?->plan_tier ?? 'free'));

        // Test 1: Get available models
        $this->info("\n=== Test 1: Available Models ===");
        try {
            $availableModels = $aiChatService->getAvailableModels($user);
            $this->info("Available models count: " . count($availableModels));
            
            foreach ($availableModels as $model) {
                $this->line("  - {$model['display_name']} ({$model['provider']})");
            }
        } catch (\Exception $e) {
            $this->error("Failed: " . $e->getMessage());
            return 1;
        }

        // Test 2: Get recommended model
        $this->info("\n=== Test 2: Recommended Model ===");
        try {
            $recommended = $aiChatService->getRecommendedModel($user);
            $this->info("Recommended: {$recommended['display_name']} ({$recommended['provider']})");
        } catch (\Exception $e) {
            $this->error("Failed: " . $e->getMessage());
            return 1;
        }

        // Test 3: Test AI generation
        $this->info("\n=== Test 3: Generate AI Response ===");
        
        if (!$this->confirm('Do you want to test actual AI generation? (This will use API credits)', false)) {
            $this->info("Skipping AI generation test");
            return 0;
        }

        try {
            $response = $aiChatService->generateResponse(
                $user,
                "You are a helpful assistant.",
                [],
                "Say hello in one sentence."
            );

            $this->info("✅ AI Response received!");
            $this->info("Model used: {$response['model_used']['display_name']}");
            $this->info("Provider: {$response['model_used']['provider']}");
            $this->info("Input tokens: {$response['input_tokens']}");
            $this->info("Output tokens: {$response['output_tokens']}");
            $this->info("Response: {$response['content']}");

        } catch (\Exception $e) {
            $this->error("Failed: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
        }

        $this->info("\n✅ All tests passed!");
        return 0;
    }
}