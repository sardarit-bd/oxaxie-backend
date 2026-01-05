<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * AI Chat Service
 * Handles communication with AI models (Gemini, Claude)
 */
class AiChatService
{
    /**
     * Generate AI response using specified model
     * 
     * @param string $model Model name
     * @param string $systemPrompt System context
     * @param array $conversationHistory Previous messages
     * @param string $userMessage Current user message
     * @return array ['content' => string, 'input_tokens' => int, 'output_tokens' => int]
     * @throws Exception
     */
    public function generateResponse(
        string $model,
        string $systemPrompt,
        array $conversationHistory,
        string $userMessage
    ): array {
        return match (true) {
            str_starts_with($model, 'gemini') => $this->callGemini($model, $systemPrompt, $conversationHistory, $userMessage),
            str_starts_with($model, 'claude') => $this->callClaude($model, $systemPrompt, $conversationHistory, $userMessage),
            default => throw new Exception('Unsupported AI model: ' . $model),
        };
    }

    /**
     * Call Google Gemini API
     */
    protected function callGemini(
        string $model,
        string $systemPrompt,
        array $conversationHistory,
        string $userMessage
    ): array {
        $apiKey = config('services.gemini.api_key');
        
        if (!$apiKey) {
            throw new Exception('Gemini API key not configured');
        }

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
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
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

            // Estimate tokens (Gemini doesn't always return token counts)
            $inputTokens = $this->estimateTokens($conversationText);
            $outputTokens = $this->estimateTokens($content);

            // Try to get actual token counts if available
            if (isset($data['usageMetadata'])) {
                $inputTokens = $data['usageMetadata']['promptTokenCount'] ?? $inputTokens;
                $outputTokens = $data['usageMetadata']['candidatesTokenCount'] ?? $outputTokens;
            }

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

    /**
     * Call Anthropic Claude API
     */
    protected function callClaude(
        string $model,
        string $systemPrompt,
        array $conversationHistory,
        string $userMessage
    ): array {
        $apiKey = config('services.claude.api_key');
        
        if (!$apiKey) {
            throw new Exception('Claude API key not configured');
        }

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
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => $model,
                    'system' => $systemPrompt,
                    'messages' => $messages,
                    'max_tokens' => 2048,
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

    /**
     * Estimate tokens from text
     * Rule of thumb: 1 token ≈ 4 characters for English text
     */
    protected function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
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
}