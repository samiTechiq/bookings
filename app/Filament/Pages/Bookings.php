<?php

namespace App\Filament\Pages;

use App\Enums\LedgerType;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Ledger;
use App\Models\Supply;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * The main interface: one row per customer summarising their bookings and supplies.
 */
class Bookings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Bookings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Business';

    protected static ?int $navigationSort = 1;

    protected ?Alignment $headerActionsAlignment = Alignment::End;

    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->can('viewAny', Booking::class);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->createBookingAction(),
            Action::make('downloadReport')
                ->label('End of Day Report')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->url(fn (): string => route('reports.daily'))
                ->openUrlInNewTab(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Customer::query()->withBookingSummary())
            ->columns([
                TextColumn::make('latest_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->description(fn (Customer $record): ?string => $record->phone),
                TextColumn::make('qty_booked')
                    ->label('Qty Booked')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->label('Amount Paid')
                    ->money('NGN')
                    ->sortable(),
                TextColumn::make('qty_supplied')
                    ->label('Qty Supply')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('balance')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state > 0 => 'warning',
                        $state < 0 => 'danger',
                        default => 'success',
                    }),
            ])
            ->defaultSort('latest_date', 'desc')
            ->stackedOnMobile()
            ->filters([
                TernaryFilter::make('pending')
                    ->label('Balance')
                    ->placeholder('All customers')
                    ->trueLabel('Pending supply')
                    ->falseLabel('Fully supplied')
                    ->queries(
                        true: fn (Builder $query) => $query->whereRaw(Customer::BALANCE_SQL.' > 0'),
                        false: fn (Builder $query) => $query->whereRaw(Customer::BALANCE_SQL.' <= 0'),
                    ),
            ])
            ->recordActions([
                $this->viewLedgerAction(),
                ActionGroup::make([
                    $this->createBookingAction('addBooking')
                        ->label('Add Booking')
                        ->fillForm(fn (Customer $record): array => [
                            'customer_id' => $record->getKey(),
                            'date' => now()->toDateString(),
                        ]),
                    Action::make('recordSupply')
                        ->label('Record Supply')
                        ->icon(Heroicon::OutlinedTruck)
                        ->modalHeading('Record Supply')
                        ->modalSubmitActionLabel('Save')
                        ->authorize(fn (): bool => (bool) Auth::user()?->can('create', Supply::class))
                        ->schema($this->movementFormSchema(withAmount: false))
                        ->fillForm(fn (Customer $record): array => [
                            'customer_id' => $record->getKey(),
                            'date' => now()->toDateString(),
                        ])
                        ->action(fn (array $data): Supply => Supply::query()->create($data))
                        ->successNotificationTitle('Supply recorded'),
                ]),
            ])
            ->emptyStateHeading('No bookings yet')
            ->emptyStateDescription('Create a booking to start tracking customer supplies.');
    }

    protected function createBookingAction(string $name = 'createBooking'): Action
    {
        return Action::make($name)
            ->label('Create Booking')
            ->icon(Heroicon::OutlinedPlus)
            ->modalHeading('Create Booking')
            ->modalSubmitActionLabel('Save')
            ->authorize(fn (): bool => (bool) Auth::user()?->can('create', Booking::class))
            ->schema($this->movementFormSchema(withAmount: true))
            ->action(fn (array $data): Booking => Booking::query()->create($data))
            ->successNotificationTitle('Booking created');
    }

    /**
     * Shared form for recording a booking (with amount) or a supply.
     *
     * @return array<Component>
     */
    protected function movementFormSchema(bool $withAmount): array
    {
        return [
            CustomerForm::select()
                ->columnSpanFull(),
            DatePicker::make('date')
                ->required()
                ->default(now()),
            TextInput::make('qty')
                ->label($withAmount ? 'Qty Booked' : 'Qty Supplied')
                ->required()
                ->integer()
                ->minValue(1)
                ->suffix('bottles'),
            TextInput::make('amount')
                ->label('Amount Paid')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->prefix('₦')
                ->visible($withAmount),
        ];
    }

    protected function viewLedgerAction(): Action
    {
        return Action::make('viewLedger')
            ->label('View Ledger')
            ->icon(Heroicon::OutlinedBookOpen)
            ->button()
            ->size('sm')
            ->modalHeading(fn (Customer $record): string => "{$record->name} — Ledger")
            ->modalWidth(Width::FourExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->slideOver()
            ->schema(fn (Customer $record): array => [
                Grid::make(['default' => 2, 'md' => 4])
                    ->schema([
                        TextEntry::make('qty_booked')
                            ->label('Qty Booked')
                            ->state($record->qty_booked)
                            ->numeric(),
                        TextEntry::make('qty_supplied')
                            ->label('Qty Supplied')
                            ->state($record->qty_supplied)
                            ->numeric(),
                        TextEntry::make('amount_paid')
                            ->label('Amount Paid')
                            ->state($record->amount_paid)
                            ->money('NGN'),
                        TextEntry::make('balance')
                            ->state($record->balance)
                            ->numeric()
                            ->weight(FontWeight::Bold)
                            ->color($record->balance > 0 ? 'warning' : 'success'),
                    ]),
                Section::make('History')
                    ->schema([
                        RepeatableEntry::make('entries')
                            ->hiddenLabel()
                            ->state($this->ledgerEntriesWithRunningBalance($record))
                            ->placeholder('No ledger entries yet.')
                            ->table([
                                TableColumn::make('Date'),
                                TableColumn::make('Type'),
                                TableColumn::make('Qty')->alignment(Alignment::End),
                                TableColumn::make('Balance')->alignment(Alignment::End),
                            ])
                            ->schema([
                                TextEntry::make('date')->date(),
                                TextEntry::make('type')->badge(),
                                TextEntry::make('qty')->numeric(),
                                TextEntry::make('running_balance')->numeric(),
                            ]),
                    ]),
            ]);
    }

    /**
     * The customer's ledger in chronological order with a running balance.
     *
     * @return list<array{date: Carbon, type: LedgerType, qty: int, running_balance: int}>
     */
    protected function ledgerEntriesWithRunningBalance(Customer $customer): array
    {
        $runningBalance = 0;

        return $customer->ledgers()
            ->orderBy('date')
            ->orderBy('created_at')
            ->get()
            ->map(function (Ledger $ledger) use (&$runningBalance): array {
                $runningBalance += $ledger->type === LedgerType::In ? $ledger->qty : -$ledger->qty;

                return [
                    'date' => $ledger->date,
                    'type' => $ledger->type,
                    'qty' => $ledger->qty,
                    'running_balance' => $runningBalance,
                ];
            })
            ->all();
    }
}
