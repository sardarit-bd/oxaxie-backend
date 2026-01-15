<?php

namespace App\Filament\Resources\SubscriptionPlanModelAccesses\Pages;

use App\Filament\Resources\SubscriptionPlanModelAccesses\SubscriptionPlanModelAccessResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionPlanModelAccesses extends ListRecords
{
    protected static string $resource = SubscriptionPlanModelAccessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->createAnother(false),
        ];
    }
}
