<?php

namespace App\Filament\Resources\AiProviderCredentials\Pages;

use App\Filament\Resources\AiProviderCredentials\AiProviderCredentialResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditAiProviderCredential extends EditRecord
{
    protected static string $resource = AiProviderCredentialResource::class;

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
