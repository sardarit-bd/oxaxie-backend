<?php

namespace App\Repositories\Contracts;

use App\Models\AiModelPricing;
use Illuminate\Database\Eloquent\Collection;

interface AiModelPricingRepositoryInterface
{
    public function getPricingForModel(int $modelId, ?string $planTier = null): ?AiModelPricing;
    
    public function getAllPricingForModel(int $modelId): Collection;
    
    public function create(array $data): AiModelPricing;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id): bool;
    
    public function bulkUpsert(int $modelId, array $pricingData): void;
}