<?php

namespace App\Filament\Resources\AiModelPricings\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DateTimePicker;


class AiModelPricingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pricing Details')
                    ->schema([
                        Select::make('ai_model_id')
                            ->relationship('model', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('subscription_plan_tier')
                            ->label('Subscription Plan Tier')
                            ->placeholder('e.g. Free, Pro, Pro Plus')
                            ->nullable(),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('input_cost_per_1m_tokens')
                                    ->label('Input Cost (per 1M tokens)')
                                    ->numeric()
                                    ->required()
                                    ->step(0.000001)
                                    ->suffix('$'),

                                TextInput::make('output_cost_per_1m_tokens')
                                    ->label('Output Cost (per 1M tokens)')
                                    ->numeric()
                                    ->required()
                                    ->step(0.000001)
                                    ->suffix('$'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('effective_from')
                                    ->label('Effective From')
                                    ->seconds(false)
                                    ->nullable(),

                                DateTimePicker::make('effective_until')
                                    ->label('Effective Until')
                                    ->seconds(false)
                                    ->nullable(),
                            ]),
                    ])
                    ->columns(2),

                Section::make('Status')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
            ]);
    }
}

