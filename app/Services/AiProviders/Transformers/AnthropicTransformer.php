<?php

namespace App\Services\AiProviders\Transformers;

use App\Models\AiModel;
use App\Services\AiProviders\Contracts\RequestTransformerInterface;

class AnthropicTransformer implements RequestTransformerInterface
{
    public function transform(
        array $messages,
        string $systemPrompt,
        AiModel $model,
        array $config = []
    ): array {
        $formattedMessages = [];

        // Format messages (system is separate)
        foreach ($messages as $message) {
            $formattedMessages[] = [
                'role' => $message['role'],
                'content' => $this->extractContent($message['content']),
            ];
        }

        return [
            'model' => $model->name,
            'system' => $systemPrompt,
            'messages' => $formattedMessages,
            'max_tokens' => $config['max_tokens'] ?? $model->max_tokens,
            'temperature' => $config['temperature'] ?? 0.7,
        ];
    }

    protected function extractContent($content): string
    {
        if (is_string($content)) {
            return $content;
        }

        if (is_array($content)) {
            foreach ($content as $item) {
                if (isset($item['type']) && $item['type'] === 'text') {
                    return $item['text'];
                }
            }
        }

        return '';
    }
}