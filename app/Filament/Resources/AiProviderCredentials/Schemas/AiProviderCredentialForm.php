<?php

namespace App\Filament\Resources\AiProviderCredentials\Schemas;

use App\Models\AiProvider;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\DateTimePicker;

class AiProviderCredentialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Credential Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('ai_provider_id')
                                    ->label('AI Provider')
                                    ->required()
                                    ->relationship('provider', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn ($state, callable $set) => 
                                        $set('provider_info', self::getProviderInfo($state))
                                    )
                                    ->helperText('Select the AI provider for this API key'),

                                Select::make('user_id')
                                    ->label('User')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->helperText('Leave empty for system-wide credential')
                                    ->placeholder('System-wide'),
                            ]),

                        Placeholder::make('provider_info')
                            ->label('Provider Info')
                            ->content(fn (callable $get) => self::getProviderInfo($get('ai_provider_id')))
                            ->visible(fn (callable $get) => !empty($get('ai_provider_id')))
                            ->columnSpanFull(),
                    ]),

                Section::make('API Key')
                    ->schema([
                        TextInput::make('api_key')
                            ->label('API Key')
                            ->required()
                            ->password()
                            ->revealable()
                            ->maxLength(500)
                            ->helperText('This will be encrypted when saved')
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText('Only active credentials will be used'),

                                DateTimePicker::make('expires_at')
                                    ->label('Expires At')
                                    ->nullable()
                                    ->native(false)
                                    ->helperText('Leave empty if never expires'),
                            ]),
                    ]),

                Section::make('Additional Configuration')
                    ->description('Optional provider-specific settings')
                    ->schema([
                        KeyValue::make('additional_config')
                            ->label('Configuration')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->columnSpanFull()
                            ->helperText('Add any extra configuration needed for this credential'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    protected static function getProviderInfo($providerId): string
    {
        if (!$providerId) {
            return 'Select a provider to see details';
        }

        $provider = AiProvider::find($providerId);
        
        if (!$provider) {
            return 'Provider not found';
        }

        return sprintf(
            "📍 Base URL: %s\n🔐 Auth Type: %s\n📊 Models: %d",
            $provider->endpoint_config['base_url'] ?? 'N/A',
            $provider->auth_config['type'] ?? 'N/A',
            $provider->models()->count()
        );
    }
}
