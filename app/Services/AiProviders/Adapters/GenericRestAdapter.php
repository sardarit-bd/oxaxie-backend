<?php

namespace App\Services\AiProviders\Adapters;

use App\Contracts\AiProviderInterface;
use App\Models\AiProvider;
use App\Models\AiProviderCredential;
use App\Models\AiModel;
use App\Services\AiProviders\Transformers\OpenAITransformer;
use App\Services\AiProviders\Transformers\AnthropicTransformer;
use App\Services\AiProviders\Transformers\GeminiTransformer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GenericRestAdapter implements AiProviderInterface
{
    protected AiProvider $provider;
    protected AiProviderCredential $credential;
    protected AiModel $model;

    public function __construct(
        AiProvider $provider,
        AiProviderCredential $credential,
        AiModel $model
    ) {
        $this->provider = $provider;
        $this->credential = $credential;
        $this->model = $model;
    }

    public function supportsModel(string $model): bool
    {
        return $this->model->name === $model;
    }

    public function generateResponse(
        string $model,
        string $systemPrompt,
        array $conversationHistory,
        string $userMessage
    ): array {
        // Build messages array
        $messages = [];
        
        foreach ($conversationHistory as $msg) {
            if ($msg['role'] !== 'system') {
                $messages[] = $msg;
            }
        }
        
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage,
        ];

        return $this->generateResponseWithMessages($model, $systemPrompt, $messages);
    }

    public function generateResponseWithMessages(
        string $model,
        string $systemPrompt,
        array $messages
    ): array {
        try {
            // 1. Build URL
            $url = $this->buildUrl();

            // 2. Build Headers
            $headers = $this->buildHeaders();

            // 3. Transform Request
            $requestBody = $this->transformRequest($messages, $systemPrompt);

            Log::info('AI Request', [
                'provider' => $this->provider->name,
                'model' => $this->model->name,
                'url' => $url,
            ]);

            // 4. Send Request
            $response = Http::timeout(60)
                ->withHeaders($headers)
                ->post($url, $requestBody);

            if (!$response->successful()) {
                Log::error('AI API Error', [
                    'provider' => $this->provider->name,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception('AI API request failed: ' . $response->body());
            }

            // 5. Parse Response
            $data = $response->json();
            return $this->parseResponse($data);

        } catch (Exception $e) {
            Log::error('AI Service Exception', [
                'provider' => $this->provider->name,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function buildUrl(): string
    {
        $config = $this->provider->endpoint_config;
        $endpoint = $config['endpoints']['chat'];

        // Replace {model} placeholder if exists
        $endpoint = str_replace('{model}', $this->model->name, $endpoint);

        $url = rtrim($config['base_url'], '/') . 
               '/' . trim($config['api_version'], '/') . 
               '/' . ltrim($endpoint, '/');

        // Add query param auth if needed
        if ($this->provider->auth_config['type'] === 'query_param') {
            $keyName = $this->provider->auth_config['key_name'];
            $url .= '?' . $keyName . '=' . $this->credential->api_key;
        }

        return $url;
    }

    protected function buildHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        $authConfig = $this->provider->auth_config;

        // Add authentication header
        if ($authConfig['type'] === 'header') {
            $headers[$authConfig['key_name']] = $this->credential->api_key;
        } elseif ($authConfig['type'] === 'bearer') {
            $prefix = $authConfig['header_prefix'] ?? 'Bearer';
            $headers['Authorization'] = $prefix . ' ' . $this->credential->api_key;
        }

        // Add additional headers if defined
        if (isset($this->provider->endpoint_config['additional_headers'])) {
            $headers = array_merge($headers, $this->provider->endpoint_config['additional_headers']);
        }

        return $headers;
    }

    protected function transformRequest(array $messages, string $systemPrompt): array
    {
        $transformer = $this->getTransformer();
        return $transformer->transform($messages, $systemPrompt, $this->model);
    }

    protected function getTransformer()
    {
        $format = $this->provider->request_transformer['message_format'];

        return match($format) {
            'openai' => new OpenAITransformer(),
            'anthropic' => new AnthropicTransformer(),
            'gemini' => new GeminiTransformer(),
            default => throw new Exception("Unsupported message format: {$format}"),
        };
    }

    protected function parseResponse(array $response): array
    {
        $parser = $this->provider->response_parser;

        $content = data_get($response, $parser['content_path'], '');
        $inputTokens = data_get($response, $parser['input_tokens_path'], 0);
        $outputTokens = data_get($response, $parser['output_tokens_path'], 0);

        if (empty($content)) {
            throw new Exception('Empty response from AI provider');
        }

        return [
            'content' => $content,
            'input_tokens' => (int) $inputTokens,
            'output_tokens' => (int) $outputTokens,
        ];
    }
}