<?php

namespace App\Filament\Resources\AiProviderCredentials\Pages;

use App\Filament\Resources\AiProviderCredentials\AiProviderCredentialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAiProviderCredential extends CreateRecord
{
    protected static string $resource = AiProviderCredentialResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'API Credential created successfully';
    }
}
