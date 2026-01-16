<?php

namespace App\Filament\Resources\AiModelPricings\Pages;

use App\Filament\Resources\AiModelPricings\AiModelPricingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiModelPricings extends ListRecords
{
    protected static string $resource = AiModelPricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
