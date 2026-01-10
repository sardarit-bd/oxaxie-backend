<?php

namespace App\Services;

use App\Contracts\AiProviderInterface;
use App\Services\AiProviders\GeminiProvider;
use App\Services\AiProviders\AnthropicProvider;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * AI Chat Service - Facade for multiple AI providers
 * Uses Strategy Pattern to support different AI providers
 */
class AiChatService
{
    protected array $providers = [];

    public function __construct()
    {
        // Register available providers
        // Gemini
        if (!empty(config('services.gemini.api_key'))) {
            try {
                $this->providers['gemini'] = new GeminiProvider();
                Log::info('Gemini provider registered');
            } catch (Exception $e) {
                Log::warning('Gemini provider failed to initialize: ' . $e->getMessage());
            }
        }

        // Anthropic
        if (!empty(config('services.anthropic.api_key'))) {
            try {
                $this->providers['anthropic'] = new AnthropicProvider();
                Log::info('Anthropic provider registered');
            } catch (Exception $e) {
                Log::warning('Anthropic provider failed to initialize: ' . $e->getMessage());
            }
        }

        if (empty($this->providers)) {
            throw new Exception('No AI providers configured. Please set GEMINI_API_KEY or ANTHROPIC_API_KEY in .env');
        }
    }

    /**
     * Get the appropriate provider for a model
     */
    protected function getProvider(string $model): AiProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supportsModel($model)) {
                return $provider;
            }
        }

        // If no provider found, throw helpful error
        $availableProviders = array_keys($this->providers);
        throw new Exception(
            "No provider found for model: {$model}. " .
            "Available providers: " . implode(', ', $availableProviders) . ". " .
            "Please configure the appropriate API key in .env"
        );
    }

    /**
     * Generate AI response using specified model
     */
    public function generateResponse(
        string $model,
        string $systemPrompt,
        array $conversationHistory,
        string $userMessage
    ): array {
        $provider = $this->getProvider($model);
        
        Log::info('Using provider for model', [
            'model' => $model,
            'provider' => get_class($provider)
        ]);

        return $provider->generateResponse(
            $model,
            $systemPrompt,
            $conversationHistory,
            $userMessage
        );
    }

    /**
     * Generate AI response using pre-built messages array (supports images)
     */
    public function generateResponseWithMessages(
        string $model,
        string $systemPrompt,
        array $messages
    ): array {
        $provider = $this->getProvider($model);
        
        Log::info('Using provider for model with messages', [
            'model' => $model,
            'provider' => get_class($provider),
            'messages_count' => count($messages)
        ]);

        return $provider->generateResponseWithMessages(
            $model,
            $systemPrompt,
            $messages
        );
    }

    /**
     * Build system prompt for legal assistant
     */
    public function buildSystemPrompt(array $caseData): string
    {
        return "You are a helpful legal information assistant. You provide educational legal information to help users understand their rights and options. You are NOT providing legal advice, but rather helping users understand general legal concepts and procedures.

        Case Information:
        - Issue Type: {$caseData['issue_type']}
        - Location: {$caseData['location_city']}, {$caseData['location_state']}, {$caseData['location_country']}
        - Situation: {$caseData['situation_description']}

        Important Guidelines:
        - Provide clear, educational information about legal concepts
        - Help users understand their general rights and options
        - Suggest documentation and record-keeping practices
        - Recommend when they should consult with a licensed attorney
        - Always remind them this is educational information, not legal advice
        - Be supportive and understanding of their situation
        - Use simple, clear language
        - Keep responses concise but comprehensive

        Always conclude responses by asking if they have any questions or if there's anything specific they'd like to explore further.";
    }

    /**
     * Check if a provider is available
     */
    public function hasProvider(string $providerName): bool
    {
        return isset($this->providers[$providerName]);
    }

    /**
     * Get list of available providers
     */
    public function getAvailableProviders(): array
    {
        return array_keys($this->providers);
    }
}