<?php

namespace App\Filament\Resources\AiModels\Pages;

use App\Filament\Resources\AiModels\AiModelResource;
use App\Models\AiModel;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAiModel extends EditRecord
{
    protected static string $resource = AiModelResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $exists = AiModel::where('name', $data['name'])
            ->where('ai_provider_id', $data['ai_provider_id'])
            ->where('id', '!=', $this->record->id)
            ->exists();

        if ($exists) {
            Notification::make()
                ->danger()
                ->title('Duplicate Entry')
                ->body('A model with this name already exists for the selected AI Provider.')
                ->send();


            $this->halt();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'AI Model updated successfully';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}