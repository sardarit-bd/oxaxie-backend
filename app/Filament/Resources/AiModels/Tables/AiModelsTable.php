<?php

namespace App\Filament\Resources\AiModels\Tables;

use App\Models\AiModel;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ForceDeleteBulkAction;

class AiModelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider.name')
                    ->label('Provider')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('display_name')
                    ->label('Model Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (AiModel $record): string => $record->name),

                // TextColumn::make('capabilities')
                //     ->label('Capabilities')
                //     ->badge()
                //     ->getStateUsing(fn (AiModel $record) => [
                //         $record->hasCapability('text') ? 'Text' : null,
                //         $record->hasCapability('vision') ? 'Vision' : null,
                //         $record->hasCapability('function_calling') ? 'Functions' : null,
                //         $record->hasCapability('streaming') ? 'Stream' : null,
                //     ])
                //     ->separator(','),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                TextColumn::make('max_tokens')
                    ->label('Max Tokens')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state)),

                TextColumn::make('context_window')
                    ->label('Context')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->tooltip(fn ($state) => number_format($state) . ' tokens'),

                TextColumn::make('priority')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->tooltip('Higher = preferred for auto-selection'),

                TextColumn::make('pricing_count')
                    ->counts('pricing')
                    ->label('Pricing Rules')
                    ->badge()
                    ->color('success'),

                TextColumn::make('created_at')
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

                // SelectFilter::make('capabilities')
                //     ->label('Capabilities')
                //     ->options([
                //         'text' => 'Text',
                //         'vision' => 'Vision',
                //         'function_calling' => 'Functions',
                //         'streaming' => 'Streaming',
                //     ])
                //     ->query(function ($query, $state) {
                //         if ($state['value']) {
                //             $query->whereJsonContains("capabilities->{$state['value']}", true);
                //         }
                //     })
                //     ->native(false),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('priority', 'desc')
            ->emptyStateHeading('No AI models yet')
            ->emptyStateDescription('Create your first AI model to get started.')
            ->emptyStateIcon('heroicon-o-cube');
    }
}
