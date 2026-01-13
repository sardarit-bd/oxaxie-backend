<?php

namespace App\Filament\Resources\Users;

use BackedEnum;
use App\Models\User;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\Users\Pages\ManageUsers;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255)
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->dehydrated(fn (string $operation): bool => $operation === 'create')
                    ->helperText(fn (string $operation): string => 
                        $operation === 'edit' ? '⚠️ Password cannot be changed here. Use "Reset Password" action.' : ''
                    )
                    ->revealable(),
                
                TextInput::make('password_confirmation')
                    ->password()
                    ->same('password')
                    ->dehydrated(false)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255)
                    ->label('Confirm Password')
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->revealable(),
                
                Select::make('account_status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspend',
                        'deleted' => 'Deleted'
                    ])
                    ->default('active')
                    ->required()
                    ->native(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                
                TextColumn::make('subscription.plan_tier')
                    ->label('Plan Tier')
                    ->sortable()
                    ->default('free')
                    ->formatStateUsing(function ($state, $record) {
                        $subscription = $record->subscription;
                        
                        if (!$subscription) {
                            return 'Free';
                        }
                        
                        return match ($subscription->plan_tier) {
                            'free' => 'Free',
                            'pro' => 'Pro',
                            'pro_plus' => 'Pro Plus',
                            'enterprise' => 'Enterprise',
                            default => ucfirst(str_replace('_', ' ', $subscription->plan_tier)),
                        };
                    })
                    ->badge()
                    ->color(fn ($record) => match ($record->subscription?->plan_tier ?? 'free') {
                        'free' => 'gray',
                        'pro' => 'success',
                        'pro_plus' => 'warning',
                        'enterprise' => 'danger',
                        default => 'gray',
                    }),
                
                TextColumn::make('subscription.status')
                    ->label('Subscription Status')
                    ->badge()
                    ->default('none')
                    ->formatStateUsing(function ($state) {
                        return $state ? ucfirst($state) : 'No Subscription';
                    })
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'cancelled' => 'warning',
                        'expired' => 'danger',
                        'past_due' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                
                SelectFilter::make('subscription.plan_tier')
                    ->label('Plan Tier')
                    ->options([
                        'free' => 'Free',
                        'pro' => 'Pro',
                        'pro_plus' => 'Pro Plus',
                        'enterprise' => 'Enterprise',
                    ])
                    ->query(function ($query, $state) {
                        if ($state['value']) {
                            $query->whereHas('subscription', function ($q) use ($state) {
                                $q->where('plan_tier', $state['value'])
                                ->where('status', 'active');
                            });
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
