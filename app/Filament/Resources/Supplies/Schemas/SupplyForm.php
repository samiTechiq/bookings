<?php

namespace App\Filament\Resources\Supplies\Schemas;

use App\Filament\Resources\Customers\Schemas\CustomerForm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupplyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                CustomerForm::select()
                    ->columnSpanFull(),
                DatePicker::make('date')
                    ->required()
                    ->default(now()),
                TextInput::make('qty')
                    ->label('Qty Supplied')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->suffix('bottles'),
            ])
            ->columns(['default' => 1, 'md' => 2]);
    }
}
