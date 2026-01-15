<?php

namespace App\Filament\Resources\SubscriptionPlanModelAccesses\Tables;

use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use App\Models\SubscriptionAiModelAccess;
use Filament\Tables\Filters\SelectFilter;

class SubscriptionPlanModelAccessesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subscription_plan_tier')
                    ->label('Plan')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'free' => 'gray',
                        'pro' => 'success',
                        'pro_plus' => 'warning',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'free' => 'Free',
                        'pro' => 'Pro',
                        'pro_plus' => 'Pro Plus',
                        default => ucfirst($state),
                    }),

                TextColumn::make('model.provider.name')
                    ->label('Provider')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('model.display_name')
                    ->label('Model')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (SubscriptionAiModelAccess $record): string => 
                        $record->model->name
                    ),

                IconColumn::make('is_allowed')
                    ->boolean()
                    ->label('Allowed')
                    ->sortable(),

                TextColumn::make('priority')
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->tooltip('Higher = preferred for auto-selection'),

                TextColumn::make('pricing')
                    ->label('Custom Pricing')
                    ->getStateUsing(function (SubscriptionAiModelAccess $record) {
                        $pricing = \App\Models\AiModelPricing::where('ai_model_id', $record->ai_model_id)
                            ->where('subscription_plan_tier', $record->subscription_plan_tier)
                            ->active()
                            ->first();

                        if ($pricing) {
                            return sprintf(
                                'In: $%s | Out: $%s',
                                number_format($pricing->input_cost_per_1m_tokens, 2),
                                number_format($pricing->output_cost_per_1m_tokens, 2)
                            );
                        }

                        return 'Default pricing';
                    })
                    ->badge()
                    ->color(fn ($state) => $state === 'Default pricing' ? 'gray' : 'success'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('subscription_plan_tier')
                    ->label('Plan')
                    ->options([
                        'free' => 'Free',
                        'pro' => 'Pro',
                        'pro_plus' => 'Pro Plus',
                    ])
                    ->native(false),

                SelectFilter::make('ai_model_id')
                    ->label('Model')
                    ->relationship('model', 'display_name')
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('is_allowed')
                    ->label('Status')
                    ->options([
                        '1' => 'Allowed',
                        '0' => 'Blocked',
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
            ->defaultSort('subscription_plan_tier', 'asc')
            ->groups([
                'subscription_plan_tier',
                'model.provider.name',
            ])
            ->emptyStateHeading('No model access rules yet')
            ->emptyStateDescription('Create rules to assign models to subscription plans.')
            ->emptyStateIcon('heroicon-o-shield-check');
    }
}
