<?php

namespace App\Filament\Resources\SubscriptionPlanModelAccesses;

use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use App\Models\SubscriptionAiModelAccess;
use App\Models\SubscriptionPlanModelAccess;
use App\Filament\Resources\SubscriptionPlanModelAccesses\Pages\EditSubscriptionPlanModelAccess;
use App\Filament\Resources\SubscriptionPlanModelAccesses\Pages\CreateSubscriptionPlanModelAccess;
use App\Filament\Resources\SubscriptionPlanModelAccesses\Pages\ListSubscriptionPlanModelAccesses;
use App\Filament\Resources\SubscriptionPlanModelAccesses\Schemas\SubscriptionPlanModelAccessForm;
use App\Filament\Resources\SubscriptionPlanModelAccesses\Tables\SubscriptionPlanModelAccessesTable;
use UnitEnum;

class SubscriptionPlanModelAccessResource extends Resource
{
    protected static ?string $model = SubscriptionAiModelAccess::class;


    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Plan Model Access';

    protected static string | UnitEnum | null $navigationGroup = 'AI Management';

    protected static ?int $navigationSort = 4;


    public static function form(Schema $schema): Schema
    {
        return SubscriptionPlanModelAccessForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionPlanModelAccessesTable::configure($table);
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
            'index' => ListSubscriptionPlanModelAccesses::route('/'),
            'create' => CreateSubscriptionPlanModelAccess::route('/create'),
            'edit' => EditSubscriptionPlanModelAccess::route('/{record}/edit'),
        ];
    }
}
