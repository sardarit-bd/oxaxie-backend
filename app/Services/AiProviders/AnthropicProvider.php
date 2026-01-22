<?php

namespace App\Services\AiProviders;

use App\Contracts\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AnthropicProvider implements AiProviderInterface
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
        
        if (empty($this->apiKey)) {
            throw new Exception('Anthropic API key not configured');
        }
    }

    public function supportsModel(string $model): bool
    {
        return str_starts_with($model, 'claude');
    }

    public function generateResponse(
        string $model,
        string $systemPrompt,
        array $conversationHistory,
        string $userMessage
    ): array {
        // Build messages array for Claude
        $messages = [];
        
        // Add conversation history
        foreach ($conversationHistory as $msg) {
            if ($msg['role'] !== 'system') {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
        }
        
        // Add current user message
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage
        ];

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => $model,
                    'system' => $systemPrompt,
                    'messages' => $messages,
                    'max_tokens' => 4096,
                    'temperature' => 0.7,
                ]);

            if (!$response->successful()) {
                Log::error('Claude API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('Failed to get response from Claude AI');
            }

            $data = $response->json();
            
            $content = $data['content'][0]['text'] ?? '';
            
            if (empty($content)) {
                throw new Exception('Empty response from Claude AI');
            }

            $inputTokens = $data['usage']['input_tokens'] ?? 0;
            $outputTokens = $data['usage']['output_tokens'] ?? 0;

            return [
                'content' => $content,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
            ];

        } catch (Exception $e) {
            Log::error('Claude API exception', [
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
            $response = Http::timeout(60)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => $model,
                    'max_tokens' => 4096,
                    'system' => $systemPrompt,
                    'messages' => $messages,
                ]);

            if (!$response->successful()) {
                Log::error('Claude API error:', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                throw new Exception('AI service error: ' . $response->body());
            }

            $result = $response->json();

            return [
                'content' => $result['content'][0]['text'] ?? '',
                'input_tokens' => $result['usage']['input_tokens'] ?? 0,
                'output_tokens' => $result['usage']['output_tokens'] ?? 0,
            ];

        } catch (Exception $e) {
            Log::error('Anthropic service exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}