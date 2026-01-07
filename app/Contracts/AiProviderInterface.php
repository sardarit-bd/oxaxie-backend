<?php

namespace App\Contracts;

interface AiProviderInterface
{
    /**
     * Generate AI response with simple text messages
     */
    public function generateResponse(
        string $model,
        string $systemPrompt,
        array $conversationHistory,
        string $userMessage
    ): array;

    /**
     * Generate AI response with complex messages
     */
    public function generateResponseWithMessages(
        string $model,
        string $systemPrompt,
        array $messages
    ): array;

    /**
     * Check if provider supports the given model
     */
    public function supportsModel(string $model): bool;
}