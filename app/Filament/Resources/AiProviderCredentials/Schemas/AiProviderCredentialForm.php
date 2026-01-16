<?php

namespace App\Filament\Resources\AiProviderCredentials\Schemas;

use Filament\Forms;
use App\Models\AiProvider;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema as FilamentSchema;
use App\Models\AiProviderCredential; // Import the Credential model

class AiProviderCredentialForm
{
    public static function configure(FilamentSchema $schema): FilamentSchema
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
                                    ->afterStateUpdated(function ($state, Set $set, callable $get, $livewire) {
                                        // Set Provider Info
                                        $set('provider_info', self::getProviderInfo($state));
                                        
                                        // Check for existing credential
                                        self::checkDuplicateAndLock($state, $get('user_id'), $set, $livewire);
                                    })
                                    ->helperText('Select the AI provider for this API key'),

                                Select::make('user_id')
                                    ->label('User')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, callable $get, $livewire) {
                                    
                                        self::checkDuplicateAndLock($get('ai_provider_id'), $state, $set, $livewire);
                                    })
                                    ->helperText('Leave empty for system-wide credential')
                                    ->placeholder('System-wide'),
                            ]),

                        Placeholder::make('provider_info')
                            ->label('Provider Info')
                            ->content(fn (callable $get) => self::getProviderInfo($get('ai_provider_id')))
                            ->visible(fn (callable $get) => !empty($get('ai_provider_id')))
                            ->columnSpanFull(),

                        // Warning Banner (Hidden by default)
                        Placeholder::make('duplicate_warning')
                            ->visible(fn (callable $get) => $get('is_duplicate'))
                            ->content('This provider already has an API key configured for this user. Please edit the existing credential instead of creating a new one.')
                            ->extraAttributes(['class' => 'text-danger-600 bg-danger-50 p-4 rounded-lg border border-danger-200']),
                    ]),

                Section::make('API Key')
                    ->schema([
                        TextInput::make('api_key')
                            ->label('API Key')
                            ->required()
                            ->password()
                            ->revealable()
                            ->maxLength(500)
                            ->disabled(fn (callable $get) => $get('is_duplicate')) // Disable if duplicate
                            ->dehydrateStateUsing(fn ($state) => $state)
                            ->helperText('This will be encrypted when saved')
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false)
                                    ->disabled(fn (callable $get) => $get('is_duplicate')) // Disable if duplicate
                                    ->helperText('Only active credentials will be used'),

                                DateTimePicker::make('expires_at')
                                    ->label('Expires At')
                                    ->nullable()
                                    ->native(false)
                                    ->disabled(fn (callable $get) => $get('is_duplicate')) // Disable if duplicate
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
                            ->disabled(fn (callable $get) => $get('is_duplicate')) // Disable if duplicate
                            ->helperText('Add any extra configuration needed for this credential'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * Helper to check if a credential already exists and lock the form if so.
     */
    protected static function checkDuplicateAndLock($providerId, $userId, Set $set, $livewire): void
    {
        $isDuplicate = false;

        if ($providerId) {
            // Check database for existing record
            $query = AiProviderCredential::where('ai_provider_id', $providerId);

            if ($userId) {
                $query->where('user_id', $userId);
            } else {
                $query->whereNull('user_id');
            }

            // In Edit mode, ignore the current record itself
            if ($livewire instanceof \Filament\Resources\Pages\EditRecord && $livewire->getRecord()) {
                $query->where('id', '!=', $livewire->getRecord()->id);
            }

            $isDuplicate = $query->exists();
        }

        $set('is_duplicate', $isDuplicate);
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