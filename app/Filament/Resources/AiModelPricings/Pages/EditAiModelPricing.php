<?php

namespace App\Filament\Resources\AiModelPricings\Pages;

use App\Filament\Resources\AiModelPricings\AiModelPricingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAiModelPricing extends EditRecord
{
    protected static string $resource = AiModelPricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
