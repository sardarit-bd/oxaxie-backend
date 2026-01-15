<?php

namespace App\Repositories\Contracts;

use App\Models\AiModel;
use Illuminate\Database\Eloquent\Collection;

interface AiModelRepositoryInterface
{
    public function all(): Collection;
    
    public function findById(int $id): ?AiModel;
    
    public function findByName(string $name): ?AiModel;
    
    public function getActive(): Collection;
    
    public function getByProvider(int $providerId): Collection;
    
    public function getForPlan(string $planTier): Collection;
    
    public function getWithCapability(string $capability): Collection;
    
    public function create(array $data): AiModel;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id): bool;
}