<?php

use App\Filament\Pages\Bookings;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Supplies\Pages\EditSupply;
use App\Filament\Resources\Supplies\SupplyResource;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Supply;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;

use function Pest\Livewire\livewire;

test('marketing managers can list and open booking entries', function () {
    $this->actingAs(User::factory()->marketingManager()->create());
    $bookings = Booking::factory()->count(3)->create();

    livewire(ListBookings::class)->assertCanSeeTableRecords($bookings);

    $this->get(BookingResource::getUrl('edit', ['record' => $bookings->first()]))->assertSuccessful();
});

test('marketing managers can edit a booking and the ledger follows', function () {
    $this->actingAs(User::factory()->marketingManager()->create());
    $booking = Booking::factory()->create(['qty' => 100, 'amount' => 1000]);

    livewire(EditBooking::class, ['record' => $booking->id])
        ->fillForm(['qty' => 80, 'amount' => 1500, 'date' => '2026-10-20'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $booking->refresh();

    expect($booking->qty)->toBe(80)
        ->and((float) $booking->amount)->toBe(1500.0)
        ->and($booking->ledger->qty)->toBe(80)
        ->and($booking->ledger->date->toDateString())->toBe('2026-10-20');
});

test('marketing managers can edit a supply', function () {
    $this->actingAs(User::factory()->marketingManager()->create());
    $supply = Supply::factory()->create(['qty' => 50]);

    livewire(EditSupply::class, ['record' => $supply->id])
        ->fillForm(['qty' => 45])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($supply->fresh()->qty)->toBe(45)
        ->and($supply->ledger->fresh()->qty)->toBe(45);
});

test('only root admins can delete booking entries', function () {
    $booking = Booking::factory()->create();

    $this->actingAs(User::factory()->marketingManager()->create());
    livewire(EditBooking::class, ['record' => $booking->id])->assertActionHidden(DeleteAction::class);

    $this->actingAs(User::factory()->rootAdmin()->create());
    livewire(EditBooking::class, ['record' => $booking->id])->callAction(DeleteAction::class);

    expect(Booking::query()->count())->toBe(0);
});

test('creating a booking entry returns to the booking entries list', function () {
    $this->actingAs(User::factory()->marketingManager()->create());

    livewire(CreateBooking::class)
        ->fillForm([
            'customer_id' => Customer::factory()->create()->id,
            'date' => today()->toDateString(),
            'qty' => 25,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(BookingResource::getUrl('index'));
});

test('the ledger links each entry to its edit page', function () {
    $this->actingAs(User::factory()->marketingManager()->create());
    $customer = Customer::factory()->create();
    $booking = Booking::factory()->for($customer)->create();
    $supply = Supply::factory()->for($customer)->create();

    livewire(Bookings::class)
        ->mountAction(TestAction::make('viewLedger')->table($customer))
        ->assertMountedActionModalSeeHtml([
            e(BookingResource::getUrl('edit', ['record' => $booking])),
            e(SupplyResource::getUrl('edit', ['record' => $supply])),
        ]);
});
