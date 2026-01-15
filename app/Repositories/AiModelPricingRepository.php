<?php

namespace App\Repositories;

use App\Models\AiModelPricing;
use App\Repositories\Contracts\AiModelPricingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AiModelPricingRepository implements AiModelPricingRepositoryInterface
{
    public function getPricingForModel(int $modelId, ?string $planTier = null): ?AiModelPricing
    {
        return AiModelPricing::where('ai_model_id', $modelId)
            ->where('subscription_plan_tier', $planTier)
            ->active()
            ->current()
            ->first();
    }

    public function getAllPricingForModel(int $modelId): Collection
    {
        return AiModelPricing::where('ai_model_id', $modelId)
            ->active()
            ->current()
            ->get();
    }

    public function create(array $data): AiModelPricing
    {
        return AiModelPricing::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return AiModelPricing::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return AiModelPricing::where('id', $id)->delete();
    }

    public function bulkUpsert(int $modelId, array $pricingData): void
    {
        DB::transaction(function () use ($modelId, $pricingData) {
            foreach ($pricingData as $planTier => $costs) {
                AiModelPricing::updateOrCreate(
                    [
                        'ai_model_id' => $modelId,
                        'subscription_plan_tier' => $planTier,
                    ],
                    [
                        'input_cost_per_1m_tokens' => $costs['input'],
                        'output_cost_per_1m_tokens' => $costs['output'],
                        'effective_from' => now(),
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}