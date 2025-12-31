<?php

namespace App\Filament\Resources\AllCases\Pages;

use App\Filament\Resources\AllCases\AllCaseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAllCases extends ManageRecords
{
    protected static string $resource = AllCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
