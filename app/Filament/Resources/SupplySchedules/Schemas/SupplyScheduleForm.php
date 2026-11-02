<?php

namespace App\Filament\Resources\SupplySchedules\Schemas;

use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Models\Customer;
use App\Models\SupplySchedule;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class SupplyScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label('Supply Date')
                    ->required()
                    ->default(now()->addDay())
                    ->rule(fn (?SupplySchedule $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                        $exists = SupplySchedule::query()
                            ->whereDate('date', $value)
                            ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                            ->exists();

                        if ($exists) {
                            $fail('A supply schedule already exists for this date. Edit that schedule instead.');
                        }
                    }),
                Repeater::make('items')
                    ->label('Customers')
                    ->relationship()
                    ->schema([
                        CustomerForm::select()
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                        TextInput::make('qty')
                            ->label('Proposed Qty')
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->suffix('bottles'),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->minItems(1)
                    ->defaultItems(1)
                    ->addActionLabel('Add customer')
                    ->reorderable(false)
                    ->hintAction(
                        Action::make('loadPendingCustomers')
                            ->label('Load pending customers')
                            ->icon(Heroicon::OutlinedArrowDownTray)
                            ->requiresConfirmation()
                            ->modalDescription('Replace the list with every customer who has an outstanding balance, using their balance as the proposed qty.')
                            ->action(fn (Repeater $component) => $component->state(
                                Customer::query()
                                    ->withPendingBalance()
                                    ->orderByDesc('balance')
                                    ->get()
                                    ->mapWithKeys(fn (Customer $customer): array => [
                                        (string) Str::uuid() => [
                                            'customer_id' => $customer->getKey(),
                                            'qty' => (int) $customer->balance,
                                        ],
                                    ])
                                    ->all(),
                            )),
                    )
                    ->columnSpanFull(),
            ]);
    }
}
