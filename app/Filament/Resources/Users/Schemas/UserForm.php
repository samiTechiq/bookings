<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;
use Illuminate\Support\Facades\Auth;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === Operation::Create->value)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->minLength(8)
                    ->helperText(fn (string $operation): ?string => $operation === Operation::Edit->value ? 'Leave blank to keep the current password.' : null),
                ToggleButtons::make('role')
                    ->options(UserRole::class)
                    ->default(UserRole::MarketingManager->value)
                    ->required()
                    ->inline()
                    ->disabled(fn (?User $record): bool => (bool) $record?->is(Auth::user()))
                    ->helperText(fn (?User $record): ?string => $record?->is(Auth::user()) ? 'You cannot change your own role.' : null),
            ])
            ->columns(['default' => 1, 'md' => 2]);
    }
}
