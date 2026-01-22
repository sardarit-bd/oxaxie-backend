<?php

namespace App\Filament\Resources\AiProviderCredentials\Pages;

use App\Filament\Resources\AiProviderCredentials\AiProviderCredentialResource;
use App\Models\AiProviderCredential;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAiProviderCredential extends CreateRecord
{
    protected static string $resource = AiProviderCredentialResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $providerId = $data['ai_provider_id'];
        $userId = $data['user_id'] ?? null;

        $exists = AiProviderCredential::where('ai_provider_id', $providerId)
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
                ->title('Duplicate Credential')
                ->body('An API key for this provider (and user scope) already exists. Please edit the existing credential.')
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
        return 'API Credential created successfully';
    }
}