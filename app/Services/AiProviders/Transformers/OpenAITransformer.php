<?php

namespace App\Services\AiProviders\Transformers;

use App\Models\AiModel;
use App\Services\AiProviders\Contracts\RequestTransformerInterface;

class OpenAITransformer implements RequestTransformerInterface
{
    public function transform(
        array $messages,
        string $systemPrompt,
        AiModel $model,
        array $config = []
    ): array {
        $formattedMessages = [];

        // Add system prompt as first message
        if (!empty($systemPrompt)) {
            $formattedMessages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        // Add conversation messages
        foreach ($messages as $message) {
            $formattedMessages[] = [
                'role' => $message['role'],
                'content' => $this->extractContent($message['content']),
            ];
        }

        return [
            'model' => $model->name,
            'messages' => $formattedMessages,
            'temperature' => $config['temperature'] ?? 0.7,
            'max_tokens' => $config['max_tokens'] ?? $model->max_tokens,
        ];
    }

    protected function extractContent($content): string
    {
        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            // Extract text from array format
            foreach ($content as $item) {
                if (isset($item['type']) && $item['type'] === 'text') {
                    return $item['text'];
                }
            }
        }

        return '';
    }
}