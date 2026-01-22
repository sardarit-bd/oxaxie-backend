<?php

namespace App\Filament\Resources\SubscriptionPlanModelAccesses\Pages;

use App\Models\AiModelPricing;
use Filament\Notifications\Notification;
use App\Models\SubscriptionAiModelAccess;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\SubscriptionPlanModelAccesses\SubscriptionPlanModelAccessResource;

class CreateSubscriptionPlanModelAccess extends CreateRecord
{
    protected static string $resource = SubscriptionPlanModelAccessResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Subscription Plan Model Access created successfully';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $exists = SubscriptionAiModelAccess::where('subscription_plan_tier', $data['subscription_plan_tier'])
            ->where('ai_model_id', $data['ai_model_id'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->danger()
                ->title('Duplicate Entry')
                ->body('This model is already assigned to this plan. Please edit the existing rule instead.')
                ->persistent()
                ->send();

            $this->halt();
        }

        return $data;
    }

    protected function afterCreate(): void
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
        }

        \Illuminate\Support\Facades\Cache::flush();
    }
}
