<?php

namespace App\Filament\Resources\AiProviders\Schemas;

use Illuminate\Support\Str;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set, $get) => 
                                        empty($get('slug')) ? $set('slug', Str::slug($state)) : null
                                    ),

                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->alphaDash(),
                            ]),

                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                Select::make('adapter_type')
                                    ->required()
                                    ->options([
                                        'generic_rest' => 'Generic REST API',
                                        'custom' => 'Custom Adapter',
                                    ])
                                    ->default('generic_rest')
                                    ->live(),

                                // TextInput::make('priority')
                                //     ->numeric()
                                //     ->default(0)
                                //     ->helperText('Higher = tried first'),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false),
                            ]),

                        TextInput::make('custom_adapter_class')
                            ->maxLength(255)
                            ->visible(fn (callable $get) => $get('adapter_type') === 'custom')
                            ->helperText('e.g., App\Services\AiProvider\Adapters\Custom\MyAdapter')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // Endpoint Configuration - Left Column
                Section::make('Endpoint Configuration')
                    ->schema([
                        TextInput::make('endpoint_config.base_url')
                            ->label('Base URL')
                            ->required()
                            ->url()
                            ->placeholder('https://api.example.com'),

                        TextInput::make('endpoint_config.api_version')
                            ->label('API Version')
                            ->required()
                            ->placeholder('v1'),

                        TextInput::make('endpoint_config.endpoints.chat')
                            ->label('Chat Endpoint')
                            ->required()
                            ->placeholder('/chat/completions'),

                        TextInput::make('endpoint_config.endpoints.vision')
                            ->label('Vision Endpoint (Optional)')
                            ->placeholder('Leave empty if same as chat'),
                    ])
                    ->columnSpan(1),

                // Authentication Configuration - Right Column
                Section::make('Authentication')
                    ->schema([
                        Select::make('auth_config.type')
                            ->label('Auth Type')
                            ->required()
                            ->options([
                                'query_param' => 'Query Parameter',
                                'header' => 'Header',
                                'bearer' => 'Bearer Token',
                            ])
                            ->default('header')
                            ->live(),

                        TextInput::make('auth_config.key_name')
                            ->label('Key/Header Name')
                            ->required()
                            ->placeholder('x-api-key')
                            ->default('x-api-key'),

                        TextInput::make('auth_config.header_prefix')
                            ->label('Header Prefix')
                            ->placeholder('Bearer')
                            ->visible(fn (callable $get) => $get('auth_config.type') === 'bearer'),
                    ])
                    ->columnSpan(1),

                // Request Transformer - Left Column
                Section::make('Request Transformer')
                    ->schema([
                        Select::make('request_transformer.message_format')
                            ->label('Message Format')
                            ->required()
                            ->options([
                                'openai' => 'OpenAI',
                                'anthropic' => 'Anthropic',
                                'gemini' => 'Gemini',
                                'custom' => 'Custom',
                            ])
                            ->default('openai'),

                        Select::make('request_transformer.system_prompt_location')
                            ->label('System Prompt Location')
                            ->required()
                            ->options([
                                'separate' => 'Separate Field',
                                'in_messages' => 'In Messages',
                                'first_message' => 'First Message',
                            ])
                            ->default('separate'),

                        KeyValue::make('request_transformer.role_mapping')
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
                        TextInput::make('response_parser.content_path')
                            ->label('Content Path')
                            ->required()
                            ->placeholder('content.0.text')
                            ->helperText('Dot notation'),

                        TextInput::make('response_parser.input_tokens_path')
                            ->label('Input Tokens Path')
                            ->placeholder('usage.input_tokens'),

                        TextInput::make('response_parser.output_tokens_path')
                            ->label('Output Tokens Path')
                            ->placeholder('usage.output_tokens'),
                    ])
                    ->columnSpan(1),
            ]);
    }
}
