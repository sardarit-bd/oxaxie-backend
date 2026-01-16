<?php

namespace App\Filament\Resources\AiModels\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class AiModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('ai_provider_id')
                    ->label('AI Provider')
                    ->relationship('provider', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('display_name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('capabilities'),
                TextInput::make('max_tokens')
                    ->required()
                    ->numeric()
                    ->default(4096),
                TextInput::make('context_window')
                    ->required()
                    ->numeric()
                    ->default(128000),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('priority')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
