<?php

namespace App\Filament\Resources\AllCases;

use App\Filament\Resources\AllCases\Pages\ManageAllCases;
use App\Models\AllCase;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AllCaseResource extends Resource
{
    protected static ?string $model = AllCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('issue_type')
                    ->options([
                        'landlord_tenant' => 'Landlord tenant',
                        'employment' => 'Employment',
                        'contracts' => 'Contracts',
                        'consumer_rights' => 'Consumer rights',
                        'family' => 'Family',
                        'other' => 'Other',
                    ])
                    ->required(),
                TextInput::make('location_city'),
                TextInput::make('location_state')
                    ->required(),
                TextInput::make('location_country')
                    ->required(),
                Textarea::make('situation_description')
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(['active' => 'Active', 'resolved' => 'Resolved', 'archived' => 'Archived'])
                    ->default('active')
                    ->required(),
                Select::make('resolution_type')
                    ->options(['won' => 'Won', 'settled' => 'Settled', 'lost' => 'Lost', 'dropped' => 'Dropped']),
                DateTimePicker::make('resolved_at'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('issue_type')
                    ->badge(),
                TextEntry::make('location_city')
                    ->placeholder('-'),
                TextEntry::make('location_state'),
                TextEntry::make('location_country'),
                TextEntry::make('situation_description')
                    ->columnSpanFull(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('resolution_type')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('resolved_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (AllCase $record): bool => $record->trashed()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('issue_type')
                    ->badge(),
                TextColumn::make('location_city')
                    ->label('City')
                    ->searchable(),
                TextColumn::make('location_state')
                    ->label('State')
                    ->searchable(),
                TextColumn::make('location_country')
                    ->label('Country')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                // TextColumn::make('resolution_type')
                //     ->label('Resolution Type')
                //     ->badge(),
                TextColumn::make('resolved_at')
                    ->label('Resolved At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAllCases::route('/'),
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
