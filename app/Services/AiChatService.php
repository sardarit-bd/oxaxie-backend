<?php

namespace App\Services;

use App\Models\User;
use App\Models\AiModel;
use App\Services\AiProviders\Factories\AiProviderFactory;
use App\Services\AiProviders\AiModelSelector;
use App\Repositories\Contracts\AiModelRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * AI Chat Service - Dynamic provider system using database configuration
 */
class AiChatService
{
    protected AiProviderFactory $providerFactory;
    protected AiModelSelector $modelSelector;
    protected AiModelRepositoryInterface $modelRepository;

    public function __construct(
        AiProviderFactory $providerFactory,
        AiModelSelector $modelSelector,
        AiModelRepositoryInterface $modelRepository
    ) {
        $this->providerFactory = $providerFactory;
        $this->modelSelector = $modelSelector;
        $this->modelRepository = $modelRepository;
    }

    /**
     * Generate AI response for a user
     */
    public function generateResponse(
        User $user,
        string $systemPrompt,
        array $conversationHistory,
        string $userMessage,
        ?string $modelName = null
    ): array {
        try {
            // Select model
            $model = $modelName 
                ? $this->modelSelector->selectByName($user, $modelName)
                : $this->modelSelector->selectForUser($user);

            Log::info('Generating AI response', [
                'user_id' => $user->id,
                'model' => $model->name,
                'provider' => $model->provider->name,
            ]);

            // Get provider adapter
            $adapter = $this->providerFactory->makeForModel($model, $user->id);

            // Generate response
            $response = $adapter->generateResponse(
                $model->name,
                $systemPrompt,
                $conversationHistory,
                $userMessage
            );

            Log::info('AI response generated', [
                'model' => $model->name,
                'input_tokens' => $response['input_tokens'],
                'output_tokens' => $response['output_tokens'],
            ]);

            // Add model info to response
            $response['model_used'] = [
                'id' => $model->id,
                'name' => $model->name,
                'display_name' => $model->display_name,
                'provider' => $model->provider->name,
            ];

            return $response;

        } catch (Exception $e) {
            Log::error('AI chat service error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate AI response with pre-built messages (supports images)
     */
    public function generateResponseWithMessages(
        User $user,
        string $systemPrompt,
        array $messages,
        ?string $modelName = null,
        ?array $requirements = []
    ): array {
        try {
            // Select model with requirements
            $model = $modelName 
                ? $this->modelSelector->selectByName($user, $modelName)
                : $this->modelSelector->selectForUser($user, $requirements);

            Log::info('Generating AI response with messages', [
                'user_id' => $user->id,
                'model' => $model->name,
                'messages_count' => count($messages),
            ]);

            // Get provider adapter
            $adapter = $this->providerFactory->makeForModel($model, $user->id);

            // Generate response
            $response = $adapter->generateResponseWithMessages(
                $model->name,
                $systemPrompt,
                $messages
            );

            Log::info('AI response generated', [
                'model' => $model->name,
                'input_tokens' => $response['input_tokens'],
                'output_tokens' => $response['output_tokens'],
            ]);

            // Add model info to response
            $response['model_used'] = [
                'id' => $model->id,
                'name' => $model->name,
                'display_name' => $model->display_name,
                'provider' => $model->provider->name,
            ];

            return $response;

        } catch (Exception $e) {
            Log::error('AI chat service error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
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
     * Get available models for a user
     */
    public function getAvailableModels(User $user): array
    {
        $models = $this->modelSelector->getAvailableModels($user);

        return $models->map(function ($model) {
            return [
                'id' => $model->id,
                'name' => $model->name,
                'display_name' => $model->display_name,
                'provider' => $model->provider->name,
                'capabilities' => $model->capabilities,
                'max_tokens' => $model->max_tokens,
                'context_window' => $model->context_window,
            ];
        })->toArray();
    }

    /**
     * Check if user can use a specific model
     */
    public function canUseModel(User $user, string $modelName): bool
    {
        return $this->modelSelector->canUseModel($user, $modelName);
    }

    /**
     * Get recommended model for a user
     */
    public function getRecommendedModel(User $user, ?array $requirements = []): array
    {
        $model = $this->modelSelector->selectForUser($user, $requirements);

        return [
            'id' => $model->id,
            'name' => $model->name,
            'display_name' => $model->display_name,
            'provider' => $model->provider->name,
            'capabilities' => $model->capabilities,
        ];
    }
}