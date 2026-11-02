<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Filament\Resources\Customers\Schemas\CustomerForm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(static::fields())
            ->columns(['default' => 1, 'md' => 2]);
    }

    /**
     * Booking fields, shared with the modal actions on the Bookings page.
     *
     * @return array<Component>
     */
    public static function fields(): array
    {
        return [
            CustomerForm::select()
                ->columnSpanFull(),
            DatePicker::make('date')
                ->required()
                ->default(now()),
            TextInput::make('qty')
                ->label('Qty Booked')
                ->required()
                ->integer()
                ->minValue(1)
                ->suffix('bottles'),
            TextInput::make('amount')
                ->label('Amount Paid')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->prefix('₦'),
        ];
    }
}
