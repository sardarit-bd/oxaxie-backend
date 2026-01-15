<?php

namespace App\Filament\Resources\AiProviderCredentials\Pages;

use App\Filament\Resources\AiProviderCredentials\AiProviderCredentialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiProviderCredentials extends ListRecords
{
    protected static string $resource = AiProviderCredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->createAnother(false),
        ];
    }
}
