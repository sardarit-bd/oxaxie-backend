<?php

namespace App\Filament\Resources\AiModelPricings;

use App\Filament\Resources\AiModelPricings\Pages\CreateAiModelPricing;
use App\Filament\Resources\AiModelPricings\Pages\EditAiModelPricing;
use App\Filament\Resources\AiModelPricings\Pages\ListAiModelPricings;
use App\Filament\Resources\AiModelPricings\Schemas\AiModelPricingForm;
use App\Filament\Resources\AiModelPricings\Tables\AiModelPricingsTable;
use App\Models\AiModelPricing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AiModelPricingResource extends Resource
{
    protected static ?string $model = AiModelPricing::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Model Pricing';

    protected static string | UnitEnum | null $navigationGroup = 'AI Management';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return AiModelPricingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiModelPricingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiModelPricings::route('/'),
            'create' => CreateAiModelPricing::route('/create'),
            'edit' => EditAiModelPricing::route('/{record}/edit'),
        ];
    }
}
