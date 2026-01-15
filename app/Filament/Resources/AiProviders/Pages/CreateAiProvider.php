<?php

namespace App\Filament\Resources\AiProviders\Pages;

use App\Filament\Resources\AiProviders\AiProviderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAiProvider extends CreateRecord
{
    protected static string $resource = AiProviderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'AI Provider created successfully';
    }
}
