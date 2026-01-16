<?php

namespace App\Filament\Resources\AiModelPricings\Pages;

use App\Filament\Resources\AiModelPricings\AiModelPricingResource;
use App\Models\AiModelPricing;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAiModelPricing extends CreateRecord
{
    protected static string $resource = AiModelPricingResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->disabled(fn (callable $get) => $get('is_duplicate')),
            $this->getCancelFormAction(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $modelId = $data['ai_model_id'] ?? null;
        $planTier = $data['subscription_plan_tier'] ?? null;

        if ($modelId) {
            $query = AiModelPricing::where('ai_model_id', $modelId);
            
            if ($planTier) {
                $query->where('subscription_plan_tier', $planTier);
            } else {
                $query->whereNull('subscription_plan_tier');
            }

            if ($query->exists()) {
                Notification::make()
                    ->danger()
                    ->title('Duplicate Entry')
                    ->body('A pricing rule for this Model and Plan Tier already exists.')
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }

        return $data;
    }
}