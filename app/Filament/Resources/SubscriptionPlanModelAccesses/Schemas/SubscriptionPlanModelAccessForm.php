<?php

namespace App\Filament\Resources\SubscriptionPlanModelAccesses\Schemas;

use App\Models\AiModel;
use Filament\Schemas\Schema;
use App\Models\AiModelPricing;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Placeholder;

class SubscriptionPlanModelAccessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Access Configuration')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('subscription_plan_tier')
                                    ->label('Subscription Plan')
                                    ->required()
                                    ->options([
                                        'free' => 'Free Plan',
                                        'pro' => 'Pro Plan',
                                        'pro_plus' => 'Pro Plus Plan',
                                    ])
                                    ->native(false)
                                    ->live()
                                    ->helperText('Select the subscription plan'),

                                Select::make('ai_model_id')
                                    ->label('AI Model')
                                    ->required()
                                    ->relationship('model', 'display_name')
                                    ->getOptionLabelFromRecordUsing(fn (AiModel $record) => 
                                        "{$record->provider->name} - {$record->display_name}"
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->helperText('Select the AI model'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_allowed')
                                    ->label('Allow Access')
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText('Users with this plan can use this model'),

                                TextInput::make('priority')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Higher = preferred for auto-selection'),
                            ]),

                        Placeholder::make('current_pricing')
                            ->label('Current Pricing')
                            ->content(function (callable $get) {
                                $modelId = $get('ai_model_id');
                                $planTier = $get('subscription_plan_tier');

                                if (!$modelId || !$planTier) {
                                    return 'Select plan and model to see pricing';
                                }

                                $pricing = AiModelPricing::where('ai_model_id', $modelId)
                                    ->where('subscription_plan_tier', $planTier)
                                    ->active()
                                    ->first();

                                if ($pricing) {
                                    return sprintf(
                                        '💰 Custom: Input $%s | Output $%s (per 1M tokens)',
                                        number_format($pricing->input_cost_per_1m_tokens, 6),
                                        number_format($pricing->output_cost_per_1m_tokens, 6)
                                    );
                                }

                                // Get default pricing
                                $defaultPricing = AiModelPricing::where('ai_model_id', $modelId)
                                    ->whereNull('subscription_plan_tier')
                                    ->active()
                                    ->first();

                                if ($defaultPricing) {
                                    return sprintf(
                                        '📊 Default: Input $%s | Output $%s (per 1M tokens)',
                                        number_format($defaultPricing->input_cost_per_1m_tokens, 6),
                                        number_format($defaultPricing->output_cost_per_1m_tokens, 6)
                                    );
                                }

                                return '⚠️ No pricing configured for this model';
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make('Custom Pricing (Optional)')
                    ->description('Override default pricing for this specific plan-model combination')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('custom_input_cost')
                                    ->label('Input Cost (per 1M tokens)')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->prefix('$')
                                    ->helperText('Leave empty to use default pricing'),

                                TextInput::make('custom_output_cost')
                                    ->label('Output Cost (per 1M tokens)')
                                    ->numeric()
                                    ->step(0.000001)
                                    ->prefix('$')
                                    ->helperText('Leave empty to use default pricing'),
                            ]),

                        Placeholder::make('pricing_note')
                            ->content('💡 Custom pricing will be saved separately and override default pricing for this plan.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
