<?php

namespace App\Filament\Resources\AiModels\Pages;

use App\Filament\Resources\AiModels\AiModelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAiModel extends CreateRecord
{
    protected static string $resource = AiModelResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'AI Model created successfully';
    }
}
