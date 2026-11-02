<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(static::fields())
            ->columns(['default' => 1, 'md' => 2]);
    }

    /**
     * Customer fields, reused by inline "create customer" options elsewhere.
     *
     * @return array<Component>
     */
    public static function fields(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('phone')
                ->tel()
                ->maxLength(50),
            Textarea::make('address')
                ->rows(2)
                ->maxLength(255)
                ->columnSpanFull(),
        ];
    }

    /**
     * Searchable customer picker with inline registration and a live balance hint.
     */
    public static function select(string $name = 'customer_id'): Select
    {
        return Select::make($name)
            ->label('Customer')
            ->options(fn (): array => Customer::query()->orderBy('name')->pluck('name', 'id')->all())
            ->searchable()
            ->required()
            ->live()
            ->createOptionForm(static::fields())
            ->createOptionUsing(fn (array $data): string => Customer::query()->create($data)->getKey())
            ->helperText(function (?string $state): ?string {
                if (blank($state)) {
                    return null;
                }

                $balance = Customer::query()->withBookingSummary()->whereKey($state)->value('balance') ?? 0;

                return 'Outstanding balance: '.Number::format((int) $balance).' bottles';
            });
    }
}
