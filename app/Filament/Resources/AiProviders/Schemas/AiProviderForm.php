<?php

namespace App\Filament\Resources\AiProviders\Schemas;

use App\Models\AiProvider;
use Illuminate\Support\Str;
use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class AiProviderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Placeholder::make('duplicate_warning')
                            ->visible(fn (callable $get) => $get('is_duplicate'))
                            ->content('An AI Provider with this Name or Slug already exists. Please choose a different name.')
                            ->extraAttributes(['class' => 'text-danger-600 bg-danger-50 p-4 rounded-lg border border-danger-200'])
                            ->columnSpanFull(),

                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    // REMOVED ->disabled()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                        if (empty($get('slug')) || $get('slug') === Str::slug($get('name'))) {
                                            $set('slug', Str::slug($state));
                                        }
                                        self::checkDuplicate($get('name'), $get('slug'), $set, $livewire);
                                    }),

                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->alphaDash()
                                    // REMOVED ->disabled()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get, $livewire) {
                                        self::checkDuplicate($get('name'), $state, $set, $livewire);
                                    }),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('adapter_type')
                                    ->required()
                                    ->options([
                                        'generic_rest' => 'Generic REST API',
                                        'custom' => 'Custom Adapter',
                                    ])
                                    ->default('generic_rest')
                                    ->live(),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false),
                            ]),

                        Forms\Components\TextInput::make('custom_adapter_class')
                            ->maxLength(255)
                            ->visible(fn (callable $get) => $get('adapter_type') === 'custom')
                            ->helperText('e.g., App\Services\AiProvider\Adapters\Custom\MyAdapter')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // Endpoint Configuration - Left Column
                Section::make('Endpoint Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('endpoint_config.base_url')
                            ->label('Base URL')
                            ->required()
                            ->url()
                            ->placeholder('https://api.example.com'),

                        Forms\Components\TextInput::make('endpoint_config.api_version')
                            ->label('API Version')
                            ->required()
                            ->placeholder('v1'),

                        Forms\Components\TextInput::make('endpoint_config.endpoints.chat')
                            ->label('Chat Endpoint')
                            ->required()
                            ->placeholder('/chat/completions'),

                        Forms\Components\TextInput::make('endpoint_config.endpoints.vision')
                            ->label('Vision Endpoint (Optional)')
                            ->placeholder('Leave empty if same as chat'),
                    ])
                    ->columnSpan(1),

                // Authentication Configuration - Right Column
                Section::make('Authentication')
                    ->schema([
                        Forms\Components\Select::make('auth_config.type')
                            ->label('Auth Type')
                            ->required()
                            ->options([
                                'query_param' => 'Query Parameter',
                                'header' => 'Header',
                                'bearer' => 'Bearer Token',
                            ])
                            ->default('header')
                            ->live(),

                        Forms\Components\TextInput::make('auth_config.key_name')
                            ->label('Key/Header Name')
                            ->required()
                            ->placeholder('x-api-key')
                            ->default('x-api-key'),

                        Forms\Components\TextInput::make('auth_config.header_prefix')
                            ->label('Header Prefix')
                            ->placeholder('Bearer')
                            ->visible(fn (callable $get) => $get('auth_config.type') === 'bearer'),
                    ])
                    ->columnSpan(1),

                Section::make('Request Transformer')
                    ->schema([
                        Forms\Components\Select::make('request_transformer.message_format')
                            ->label('Message Format')
                            ->required()
                            ->options([
                                'openai' => 'OpenAI',
                                'anthropic' => 'Anthropic',
                                'gemini' => 'Gemini',
                                'custom' => 'Custom',
                            ])
                            ->default('openai'),

                        Forms\Components\Select::make('request_transformer.system_prompt_location')
                            ->label('System Prompt Location')
                            ->required()
                            ->options([
                                'separate' => 'Separate Field',
                                'in_messages' => 'In Messages',
                                'first_message' => 'First Message',
                            ])
                            ->default('separate'),

                        Forms\Components\KeyValue::make('request_transformer.role_mapping')
                            ->label('Role Mapping')
                            ->keyLabel('From')
                            ->valueLabel('To')
                            ->default([
                                'user' => 'user',
                                'assistant' => 'assistant',
                            ])
                            ->helperText('Map roles to provider format'),
                    ])
                    ->columnSpan(1),

                // Response Parser - Right Column
                Section::make('Response Parser')
                    ->schema([
                        Forms\Components\TextInput::make('response_parser.content_path')
                            ->label('Content Path')
                            ->required()
                            ->placeholder('content.0.text')
                            ->helperText('Dot notation'),

                        Forms\Components\TextInput::make('response_parser.input_tokens_path')
                            ->label('Input Tokens Path')
                            ->placeholder('usage.input_tokens'),

                        Forms\Components\TextInput::make('response_parser.output_tokens_path')
                            ->label('Output Tokens Path')
                            ->placeholder('usage.output_tokens'),
                    ])
                    ->columnSpan(1),
            ]);
    }

    protected static function checkDuplicate($name, $slug, callable $set, $livewire): void
    {
        if (empty($name) && empty($slug)) {
            $set('is_duplicate', false);
            return;
        }

        $query = AiProvider::query();

        if ($livewire instanceof \Filament\Resources\Pages\EditRecord && $livewire->getRecord()) {
            $query->where('id', '!=', $livewire->getRecord()->id);
        }

        $isDuplicate = $query->where(function ($q) use ($name, $slug) {
            $q->where('name', $name)
              ->orWhere('slug', $slug);
        })->exists();

        $set('is_duplicate', $isDuplicate);
    }
}