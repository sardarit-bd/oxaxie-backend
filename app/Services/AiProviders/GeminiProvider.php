<?php

namespace App\Services\AiProviders;

use App\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiProvider implements AiProviderInterface
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        
        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key not configured');
        }
    }

    public function supportsModel(string $model): bool
    {
        return str_starts_with($model, 'gemini');
    }

    public function generateResponse(
        string $model,
        string $systemPrompt,
        array $conversationHistory,
        string $userMessage
    ): array {
        // Build conversation text
        $conversationText = $systemPrompt . "\n\n";
        
        // Add recent history (last 5 messages)
        $recentHistory = array_slice($conversationHistory, -5);
        foreach ($recentHistory as $msg) {
            $role = $msg['role'] === 'user' ? 'User' : 'Assistant';
            $conversationText .= "{$role}: {$msg['content']}\n\n";
        }
        
        $conversationText .= "User: {$userMessage}\n\nAssistant:";

        try {

            $response = Http::timeout(60)
                ->post("https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $conversationText]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 2048,
                    ]
                ]);

            if (!$response->successful()) {
                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('Failed to get response from Gemini AI');
            }

            $data = $response->json();
            
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            if (empty($content)) {
                throw new Exception('Empty response from Gemini AI');
            }

            // Get token counts
            $inputTokens = $data['usageMetadata']['promptTokenCount'] ?? $this->estimateTokens($conversationText);
            $outputTokens = $data['usageMetadata']['candidatesTokenCount'] ?? $this->estimateTokens($content);

            return [
                'content' => $content,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
            ];

        } catch (Exception $e) {
            Log::error('Gemini API exception', [
                'message' => $e->getMessage(),
                'model' => $model
            ]);
            throw $e;
        }
    }

    public function generateResponseWithMessages(
        string $model,
        string $systemPrompt,
        array $messages
    ): array {

        try {
            $contents = [];

            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $systemPrompt]]
            ];
            
            $contents[] = [
                'role' => 'model',
                'parts' => [['text' => 'I understand. I will provide helpful legal information following these guidelines.']]
            ];

            // Process conversation messages
            foreach ($messages as $message) {
                $role = $message['role'] === 'assistant' ? 'model' : 'user';
                $parts = [];

                // Handle different content types
                if (is_string($message['content'])) {
                    // Simple text message
                    $parts[] = ['text' => $message['content']];
                } elseif (is_array($message['content'])) {
                    // Complex message with images
                    foreach ($message['content'] as $content) {
                        if ($content['type'] === 'text') {
                            $parts[] = ['text' => $content['text']];
                        } elseif ($content['type'] === 'image' && isset($content['source'])) {
                            // Convert image to Gemini format
                            $parts[] = [
                                'inline_data' => [
                                    'mime_type' => $content['source']['media_type'],
                                    'data' => $content['source']['data']
                                ]
                            ];
                            Log::info('Added image to Gemini request', [
                                'mime_type' => $content['source']['media_type'],
                                'data_length' => strlen($content['source']['data'])
                            ]);
                        }
                    }
                }

                $contents[] = [
                    'role' => $role,
                    'parts' => $parts
                ];
            }

            // Use v1beta for vision/multimodal (required for images)
            $response = Http::timeout(60)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}", [
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 2048,
                    ]
                ]);

            if (!$response->successful()) {
                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('Failed to get response from Gemini AI: ' . $response->body());
            }

            $data = $response->json();
            
            $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            if (empty($content)) {
                throw new Exception('Empty response from Gemini AI');
            }

            // Get token counts
            $inputTokens = $data['usageMetadata']['promptTokenCount'] ?? 0;
            $outputTokens = $data['usageMetadata']['candidatesTokenCount'] ?? 0;

            Log::info('Gemini response received', [
                'content_length' => strlen($content),
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens
            ]);

            return [
                'content' => $content,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
            ];

        } catch (Exception $e) {
            Log::error('Gemini API exception', [
                'message' => $e->getMessage(),
                'model' => $model,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Estimate tokens from text
     * Rule of thumb: 1 token ≈ 4 characters for English text
     */
    protected function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }
}