<?php

namespace App\Filament\Resources\AiProviders\Tables;

use App\Models\AiProvider;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ForceDeleteBulkAction;

class AiProvidersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (AiProvider $record): string => $record->description ?? ''),

                TextColumn::make('slug')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('adapter_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'generic_rest' => 'success',
                        'custom' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'generic_rest' => 'Generic REST',
                        'custom' => 'Custom',
                        default => $state,
                    }),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                TextColumn::make('models_count')
                    ->counts('models')
                    ->label('Models')
                    ->badge()
                    ->color('success'),

                TextColumn::make('credentials_count')
                    ->counts('credentials')
                    ->label('API Keys')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->native(false),

                SelectFilter::make('adapter_type')
                    ->label('Adapter Type')
                    ->options([
                        'generic_rest' => 'Generic REST',
                        'custom' => 'Custom',
                    ])
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('priority', 'desc')
            ->emptyStateHeading('No AI providers yet')
            ->emptyStateDescription('Create your first AI provider to get started.')
            ->emptyStateIcon('heroicon-o-cpu-chip');
    }
}
