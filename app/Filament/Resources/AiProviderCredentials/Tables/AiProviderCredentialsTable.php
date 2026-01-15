<?php

namespace App\Filament\Resources\AiProviderCredentials\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use App\Models\AiProviderCredential;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ForceDeleteBulkAction;

class AiProviderCredentialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider.name')
                    ->label('Provider')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->badge()
                    ->color('info'),

                TextColumn::make('masked_key')
                    ->label('API Key')
                    ->getStateUsing(fn (AiProviderCredential $record) => $record->getMaskedApiKey())
                    ->copyable()
                    ->copyableState(fn (AiProviderCredential $record) => $record->getMaskedApiKey())
                    ->copyMessage('Masked key copied')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('user.name')
                    ->label('User')
                    ->default('System-wide')
                    ->badge()
                    ->color(fn ($state) => $state === 'System-wide' ? 'success' : 'warning')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never')
                    ->color(fn ($state) => $state && $state < now() ? 'danger' : 'success'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('ai_provider_id')
                    ->label('Provider')
                    ->relationship('provider', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No API credentials yet')
            ->emptyStateDescription('Add your first API key to start using AI providers.')
            ->emptyStateIcon('heroicon-o-key');
    }
}
