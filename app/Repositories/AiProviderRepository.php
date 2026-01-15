<?php

namespace App\Repositories;

use App\Models\AiProvider;
use App\Repositories\Contracts\AiProviderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class AiProviderRepository implements AiProviderRepositoryInterface
{
    protected const CACHE_TTL = 3600; // 1 hour

    public function all(): Collection
    {
        return Cache::remember('ai_providers.all', self::CACHE_TTL, function () {
            return AiProvider::with(['credentials', 'models'])->get();
        });
    }

    public function findById(int $id): ?AiProvider
    {
        return Cache::remember("ai_providers.{$id}", self::CACHE_TTL, function () use ($id) {
            return AiProvider::with(['credentials', 'models'])->find($id);
        });
    }

    public function findBySlug(string $slug): ?AiProvider
    {
        return Cache::remember("ai_providers.slug.{$slug}", self::CACHE_TTL, function () use ($slug) {
            return AiProvider::with(['credentials', 'models'])
                ->where('slug', $slug)
                ->first();
        });
    }

    public function getActive(): Collection
    {
        return Cache::remember('ai_providers.active', self::CACHE_TTL, function () {
            return AiProvider::with(['credentials', 'models'])
                ->active()
                ->orderBy('priority', 'desc')
                ->get();
        });
    }

    public function create(array $data): AiProvider
    {
        $provider = AiProvider::create($data);
        $this->clearCache();
        return $provider->load(['credentials', 'models']);
    }

    public function update(int $id, array $data): bool
    {
        $result = AiProvider::where('id', $id)->update($data);
        $this->clearCache();
        return $result;
    }

    public function delete(int $id): bool
    {
        $result = AiProvider::where('id', $id)->delete();
        $this->clearCache();
        return $result;
    }

    public function getWithCredentials(?int $userId = null): Collection
    {
        $cacheKey = $userId ? "ai_providers.credentials.user.{$userId}" : 'ai_providers.credentials.system';
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            return AiProvider::with(['credentials' => function ($query) use ($userId) {
                $query->active()->where('user_id', $userId);
            }])->active()->get();
        });
    }

    public function hasActiveCredential(int $providerId, ?int $userId = null): bool
    {
        $provider = $this->findById($providerId);
        return $provider && $provider->hasActiveCredential($userId);
    }

    protected function clearCache(): void
    {
        Cache::forget('ai_providers.all');
        Cache::forget('ai_providers.active');
        // Clear other related caches as needed
    }
}