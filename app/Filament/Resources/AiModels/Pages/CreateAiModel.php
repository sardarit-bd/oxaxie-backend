<?php

namespace App\Filament\Resources\AiModels\Pages;

use App\Filament\Resources\AiModels\AiModelResource;
use App\Models\AiModel;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAiModel extends CreateRecord
{
    protected static string $resource = AiModelResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $exists = AiModel::where('name', $data['name'])
            ->where('ai_provider_id', $data['ai_provider_id'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->danger()
                ->title('Duplicate Entry')
                ->body('A model with this name already exists for the selected AI Provider.')
                ->persistent()
                ->send();
                
            $this->halt();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'AI Model created successfully';
    }
}