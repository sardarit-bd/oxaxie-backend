<?php

namespace App\Services\AiProviders\Transformers;

use App\Models\AiModel;
use App\Services\AiProviders\Contracts\RequestTransformerInterface;

class GeminiTransformer implements RequestTransformerInterface
{
    public function transform(
        array $messages,
        string $systemPrompt,
        AiModel $model,
        array $config = []
    ): array {
        $contents = [];

        // Add system prompt as first user-model exchange
        if (!empty($systemPrompt)) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $systemPrompt]],
            ];
            $contents[] = [
                'role' => 'model',
                'parts' => [['text' => 'I understand. I will follow these instructions.']],
            ];
        }

        // Add conversation messages with role mapping
        foreach ($messages as $message) {
            $role = $message['role'] === 'assistant' ? 'model' : 'user';
            
            $contents[] = [
                'role' => $role,
                'parts' => $this->transformContent($message['content']),
            ];
        }

        return [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $config['temperature'] ?? 0.7,
                'maxOutputTokens' => $config['max_tokens'] ?? $model->max_tokens,
            ],
        ];
    }

    protected function transformContent($content): array
    {
        if (is_string($content)) {
            return [['text' => $content]];
        }

        if (is_array($content)) {
            $parts = [];
            foreach ($content as $item) {
                if (isset($item['type']) && $item['type'] === 'text') {
                    $parts[] = ['text' => $item['text']];
                } elseif (isset($item['type']) && $item['type'] === 'image') {
                    // Handle image if needed
                    if (isset($item['source'])) {
                        $parts[] = [
                            'inline_data' => [
                                'mime_type' => $item['source']['media_type'],
                                'data' => $item['source']['data'],
                            ],
                        ];
                    }
                }
            }
            return $parts;
        }

        return [['text' => '']];
    }
}