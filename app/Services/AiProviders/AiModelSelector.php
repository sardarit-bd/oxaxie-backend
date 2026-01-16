<?php

namespace App\Services\AiProviders;

use App\Models\AiModel;
use App\Models\User;
use App\Repositories\Contracts\AiModelRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Exception;

class AiModelSelector
{
    protected AiModelRepositoryInterface $modelRepository;

    public function __construct(AiModelRepositoryInterface $modelRepository)
    {
        $this->modelRepository = $modelRepository;
    }

    /**
     * Select the best model for a user based on their plan
     */
    public function selectForUser(User $user, ?array $requirements = []): AiModel
    {
        $planTier = $user->subscription?->plan_tier ?? 'free';

        Log::info('Selecting model for user', [
            'user_id' => $user->id,
            'plan' => $planTier,
            'requirements' => $requirements,
        ]);

        // Get models available for this plan
        $availableModels = $this->modelRepository->getForPlan($planTier);

        if ($availableModels->isEmpty()) {
            throw new Exception("No models available for plan tier: {$planTier}");
        }

        // Filter by requirements if specified
        if (!empty($requirements)) {
            $availableModels = $this->filterByRequirements($availableModels, $requirements);
        }

        if ($availableModels->isEmpty()) {
            throw new Exception('No models match the specified requirements');
        }

        // Get the highest priority model
        $selectedModel = $availableModels->first();

        Log::info('Model selected', [
            'model' => $selectedModel->name,
            'provider' => $selectedModel->provider->name,
        ]);

        return $selectedModel;
    }

    /**
     * Select a specific model by name (if user has access)
     */
    public function selectByName(User $user, string $modelName): AiModel
    {
        $planTier = $user->subscription?->plan_tier ?? 'free';

        $model = $this->modelRepository->findByName($modelName);

        if (!$model) {
            throw new Exception("Model not found: {$modelName}");
        }

        // Check if user's plan has access to this model
        if (!$model->isAllowedForPlan($planTier)) {
            throw new Exception("Model {$modelName} is not available for your subscription plan");
        }

        return $model;
    }

    /**
     * Get all available models for a user
     */
    public function getAvailableModels(User $user): \Illuminate\Support\Collection
    {
        $planTier = $user->subscription?->plan_tier ?? 'free';
        return $this->modelRepository->getForPlan($planTier);
    }

    /**
     * Filter models by requirements
     */
    protected function filterByRequirements($models, array $requirements)
    {
        return $models->filter(function ($model) use ($requirements) {
            // Check for required capabilities
            if (isset($requirements['capabilities'])) {
                foreach ($requirements['capabilities'] as $capability) {
                    if (!$model->hasCapability($capability)) {
                        return false;
                    }
                }
            }

            // Check for minimum context window
            if (isset($requirements['min_context_window'])) {
                if ($model->context_window < $requirements['min_context_window']) {
                    return false;
                }
            }

            // Check for specific provider
            if (isset($requirements['provider'])) {
                if ($model->provider->slug !== $requirements['provider']) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Check if a user can use a specific model
     */
    public function canUseModel(User $user, string $modelName): bool
    {
        try {
            $this->selectByName($user, $modelName);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}