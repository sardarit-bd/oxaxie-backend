<?php

namespace App\Repositories;

use App\Models\AiModel;
use App\Repositories\Contracts\AiModelRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class AiModelRepository implements AiModelRepositoryInterface
{
    protected const CACHE_TTL = 3600;

    public function all(): Collection
    {
        return Cache::remember('ai_models.all', self::CACHE_TTL, function () {
            return AiModel::with(['provider', 'pricing', 'subscriptionAccess'])->get();
        });
    }

    public function findById(int $id): ?AiModel
    {
        return Cache::remember("ai_models.{$id}", self::CACHE_TTL, function () use ($id) {
            return AiModel::with(['provider', 'pricing', 'subscriptionAccess'])->find($id);
        });
    }

    public function findByName(string $name): ?AiModel
    {
        return Cache::remember("ai_models.name.{$name}", self::CACHE_TTL, function () use ($name) {
            return AiModel::with(['provider', 'pricing', 'subscriptionAccess'])
                ->where('name', $name)
                ->first();
        });
    }

    public function getActive(): Collection
    {
        return Cache::remember('ai_models.active', self::CACHE_TTL, function () {
            return AiModel::with(['provider', 'pricing'])
                ->active()
                ->orderBy('priority', 'desc')
                ->get();
        });
    }

    public function getByProvider(int $providerId): Collection
    {
        return Cache::remember("ai_models.provider.{$providerId}", self::CACHE_TTL, function () use ($providerId) {
            return AiModel::with(['pricing', 'subscriptionAccess'])
                ->where('ai_provider_id', $providerId)
                ->active()
                ->get();
        });
    }

    public function getForPlan(string $planTier): Collection
    {
        return Cache::remember("ai_models.plan.{$planTier}", self::CACHE_TTL, function () use ($planTier) {
            return AiModel::with(['provider', 'pricing'])
                ->whereHas('subscriptionAccess', function ($query) use ($planTier) {
                    $query->where('subscription_plan_tier', $planTier)
                        ->where('is_allowed', true);
                })
                ->active()
                ->orderByDesc(function ($query) use ($planTier) {
                    $query->select('priority')
                        ->from('subscription_ai_model_access')
                        ->whereColumn('ai_model_id', 'ai_models.id')
                        ->where('subscription_plan_tier', $planTier);
                })
                ->get();
        });
    }

    public function getWithCapability(string $capability): Collection
    {
        return AiModel::with(['provider', 'pricing'])
            ->active()
            ->whereJsonContains("capabilities->{$capability}", true)
            ->get();
    }

    public function create(array $data): AiModel
    {
        $model = AiModel::create($data);
        $this->clearCache();
        return $model->load(['provider', 'pricing', 'subscriptionAccess']);
    }

    public function update(int $id, array $data): bool
    {
        $result = AiModel::where('id', $id)->update($data);
        $this->clearCache();
        return $result;
    }

    public function delete(int $id): bool
    {
        $result = AiModel::where('id', $id)->delete();
        $this->clearCache();
        return $result;
    }

    protected function clearCache(): void
    {
        Cache::forget('ai_models.all');
        Cache::forget('ai_models.active');
    }
}