<?php

use App\Enums\LedgerType;
use App\Filament\Pages\Bookings;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Ledger;
use App\Models\Supply;
use App\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->manager = User::factory()->marketingManager()->create();
    $this->actingAs($this->manager);
});

test('lists one row per customer with booking totals', function () {
    $customer = Customer::factory()->create();
    Booking::factory()->for($customer)->count(2)->create(['qty' => 50, 'amount' => 1000]);
    Supply::factory()->for($customer)->create(['qty' => 30]);
    $inactive = Customer::factory()->create();

    livewire(Bookings::class)
        ->assertCanSeeTableRecords([$customer])
        ->assertCanNotSeeTableRecords([$inactive])
        ->assertTableColumnStateSet('qty_booked', 100, $customer)
        ->assertTableColumnStateSet('qty_supplied', 30, $customer)
        ->assertTableColumnStateSet('balance', 70, $customer);
});

test('create booking action records a booking and ledger entry for the manager', function () {
    $customer = Customer::factory()->create();

    livewire(Bookings::class)
        ->callAction('createBooking', [
            'customer_id' => $customer->id,
            'date' => today()->toDateString(),
            'qty' => 200,
            'amount' => 15000,
        ])
        ->assertHasNoFormErrors()
        ->assertNotified();

    $booking = Booking::query()->sole();

    expect($booking->customer_id)->toBe($customer->id)
        ->and($booking->qty)->toBe(200)
        ->and((float) $booking->amount)->toBe(15000.0)
        ->and($booking->user_id)->toBe($this->manager->id)
        ->and(Ledger::query()->sole()->type)->toBe(LedgerType::In);
});

test('create booking validates required fields', function () {
    livewire(Bookings::class)
        ->callAction('createBooking', ['customer_id' => null, 'qty' => null])
        ->assertHasFormErrors(['customer_id' => 'required', 'qty' => 'required']);

    expect(Booking::query()->count())->toBe(0);
});

test('record supply row action records a supply for that customer', function () {
    $customer = Customer::factory()->create();
    Booking::factory()->for($customer)->create(['qty' => 100]);

    livewire(Bookings::class)
        ->callAction(TestAction::make('recordSupply')->table($customer), [
            'qty' => 60,
        ])
        ->assertHasNoFormErrors();

    $supply = Supply::query()->sole();

    expect($supply->customer_id)->toBe($customer->id)
        ->and($supply->qty)->toBe(60)
        ->and($supply->user_id)->toBe($this->manager->id);
});

test('view ledger action shows the customer history', function () {
    $customer = Customer::factory()->create();
    Booking::factory()->for($customer)->create(['qty' => 100, 'date' => '2026-10-01']);
    Supply::factory()->for($customer)->create(['qty' => 25, 'date' => '2026-10-02']);

    livewire(Bookings::class)
        ->mountAction(TestAction::make('viewLedger')->table($customer))
        ->assertActionMounted(TestAction::make('viewLedger')->table($customer))
        ->assertMountedActionModalSee(['Booked (In)', 'Supplied (Out)', '75']);
});

test('bookings can be recorded for past and future dates', function (string $date) {
    $customer = Customer::factory()->create();

    livewire(Bookings::class)
        ->callAction('createBooking', [
            'customer_id' => $customer->id,
            'date' => $date,
            'qty' => 10,
        ])
        ->assertHasNoFormErrors();

    expect(Booking::query()->sole()->date->toDateString())->toBe($date);
})->with([
    'past' => fn (): string => today()->subWeek()->toDateString(),
    'future' => fn (): string => today()->addWeek()->toDateString(),
]);
