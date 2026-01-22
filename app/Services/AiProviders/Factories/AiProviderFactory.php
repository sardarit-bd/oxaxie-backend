<?php

namespace App\Services\AiProviders\Factories;

use App\Contracts\AiProviderInterface;
use App\Models\AiProvider;
use App\Models\AiProviderCredential;
use App\Models\AiModel;
use App\Services\AiProviders\Adapters\GenericRestAdapter;
use App\Repositories\Contracts\AiProviderRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Log;

class AiProviderFactory
{
    protected AiProviderRepositoryInterface $providerRepository;

    public function __construct(AiProviderRepositoryInterface $providerRepository)
    {
        $this->providerRepository = $providerRepository;
    }

    /**
     * Create an AI provider adapter for a specific model
     * 
     * Updated signature to accept string|int|null for $userId to handle API payloads.
     */
    public function makeForModel(AiModel $model, int|string|null $userId = null): AiProviderInterface
    {

        if ($userId !== null) {
            $userId = (int) $userId;
        }

        // Get the provider
        $provider = $model->provider;

        if (!$provider->is_active) {
            throw new Exception("Provider {$provider->name} is not active");
        }

        // Get credentials
        $credential = $this->getCredential($provider, $userId);

        if (!$credential) {
            throw new Exception("No active credentials found for provider {$provider->name}");
        }

        // Create adapter based on type
        return $this->createAdapter($provider, $credential, $model);
    }

    /**
     * Get credential for provider
     */
    protected function getCredential(AiProvider $provider, ?int $userId = null): ?AiProviderCredential
    {
        // Try user-specific credential first
        if ($userId) {
            $credential = $provider->credentials()
                ->where('user_id', $userId)
                ->active()
                ->first();

            if ($credential) {
                return $credential;
            }
        }

        // Fall back to system-wide credential
        return $provider->credentials()
            ->whereNull('user_id')
            ->active()
            ->first();
    }

    /**
     * Create adapter instance
     */
    protected function createAdapter(
        AiProvider $provider,
        AiProviderCredential $credential,
        AiModel $model
    ): AiProviderInterface {
        if ($provider->adapter_type === 'custom' && !empty($provider->custom_adapter_class)) {
            return $this->createCustomAdapter($provider, $credential, $model);
        }

        return new GenericRestAdapter($provider, $credential, $model);
    }

    /**
     * Create custom adapter (if needed in the future)
     */
    protected function createCustomAdapter(
        AiProvider $provider,
        AiProviderCredential $credential,
        AiModel $model
    ): AiProviderInterface {
        $adapterClass = $provider->custom_adapter_class;

        if (!class_exists($adapterClass)) {
            Log::warning("Custom adapter class not found: {$adapterClass}, falling back to generic");
            return new GenericRestAdapter($provider, $credential, $model);
        }

        try {
            return new $adapterClass($provider, $credential, $model);
        } catch (Exception $e) {
            Log::error("Failed to create custom adapter: {$e->getMessage()}");
            throw new Exception("Failed to initialize custom adapter for {$provider->name}");
        }
    }
}