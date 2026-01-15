<?php

namespace App\Filament\Resources\AiProviderCredentials;

use App\Filament\Resources\AiProviderCredentials\Pages\CreateAiProviderCredential;
use App\Filament\Resources\AiProviderCredentials\Pages\EditAiProviderCredential;
use App\Filament\Resources\AiProviderCredentials\Pages\ListAiProviderCredentials;
use App\Filament\Resources\AiProviderCredentials\Schemas\AiProviderCredentialForm;
use App\Filament\Resources\AiProviderCredentials\Tables\AiProviderCredentialsTable;
use App\Models\AiProviderCredential;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AiProviderCredentialResource extends Resource
{
    protected static ?string $model = AiProviderCredential::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'API Credentials';

    protected static string | UnitEnum | null $navigationGroup = 'AI Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return AiProviderCredentialForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiProviderCredentialsTable::configure($table);
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
            'index' => ListAiProviderCredentials::route('/'),
            'create' => CreateAiProviderCredential::route('/create'),
            'edit' => EditAiProviderCredential::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
