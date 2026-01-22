<?php

namespace App\Filament\Resources\AiProviderCredentials\Pages;

use App\Filament\Resources\AiProviderCredentials\AiProviderCredentialResource;
use App\Models\AiProviderCredential;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAiProviderCredential extends EditRecord
{
    protected static string $resource = AiProviderCredentialResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $providerId = $data['ai_provider_id'];
        $userId = $data['user_id'] ?? null;


        $exists = AiProviderCredential::where('ai_provider_id', $providerId)
            ->where('id', '!=', $this->record->id) 
            ->where(function ($query) use ($userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->whereNull('user_id');
                }
            })
            ->exists();

        if ($exists) {
            Notification::make()
                ->danger()
                ->title('Conflict Detected')
                ->body('Another API key is already configured for this provider and user scope.')
                ->persistent()
                ->send();

            $this->halt();
        }

        return $data;
    }

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
        return 'API Credential updated successfully';
    }
}