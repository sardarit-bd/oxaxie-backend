<?php

namespace App\Filament\Resources\SubscriptionPlanModelAccesses\Pages;

use App\Models\AiModelPricing;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\SubscriptionPlanModelAccesses\SubscriptionPlanModelAccessResource;

class EditSubscriptionPlanModelAccess extends EditRecord
{
    protected static string $resource = SubscriptionPlanModelAccessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Access rule updated successfully';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load custom pricing if exists
        $pricing = AiModelPricing::where('ai_model_id', $this->record->ai_model_id)
            ->where('subscription_plan_tier', $this->record->subscription_plan_tier)
            ->active()
            ->first();

        if ($pricing) {
            $data['custom_input_cost'] = $pricing->input_cost_per_1m_tokens;
            $data['custom_output_cost'] = $pricing->output_cost_per_1m_tokens;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->data;

        if (!empty($data['custom_input_cost']) && !empty($data['custom_output_cost'])) {
            AiModelPricing::updateOrCreate(
                [
                    'ai_model_id' => $this->record->ai_model_id,
                    'subscription_plan_tier' => $this->record->subscription_plan_tier,
                ],
                [
                    'input_cost_per_1m_tokens' => $data['custom_input_cost'],
                    'output_cost_per_1m_tokens' => $data['custom_output_cost'],
                    'effective_from' => now(),
                    'is_active' => true,
                ]
            );
        } else {
            AiModelPricing::where('ai_model_id', $this->record->ai_model_id)
                ->where('subscription_plan_tier', $this->record->subscription_plan_tier)
                ->delete();
        }

        // Clear cache
        \Illuminate\Support\Facades\Cache::flush();
    }
}
