<?php

use App\Enums\LedgerType;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Ledger;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

test('creating a booking records an in ledger entry', function () {
    $booking = Booking::factory()->create(['qty' => 120, 'date' => '2026-11-01']);

    expect(Str::isUuid($booking->id))->toBeTrue()
        ->and($booking->ledger)->not->toBeNull()
        ->and($booking->ledger->type)->toBe(LedgerType::In)
        ->and($booking->ledger->qty)->toBe(120)
        ->and($booking->ledger->customer_id)->toBe($booking->customer_id)
        ->and($booking->ledger->date->toDateString())->toBe('2026-11-01')
        ->and($booking->ledger->supply_id)->toBeNull();
});

test('creating a supply records an out ledger entry', function () {
    $supply = Supply::factory()->create(['qty' => 40]);

    expect($supply->ledger->type)->toBe(LedgerType::Out)
        ->and($supply->ledger->qty)->toBe(40)
        ->and($supply->ledger->request_id)->toBeNull();
});

test('updating a booking keeps its ledger entry in sync', function () {
    $booking = Booking::factory()->create(['qty' => 100]);

    $booking->update(['qty' => 150, 'date' => '2026-10-15']);

    expect(Ledger::query()->count())->toBe(1)
        ->and($booking->ledger->fresh()->qty)->toBe(150)
        ->and($booking->ledger->fresh()->date->toDateString())->toBe('2026-10-15');
});

test('deleting a supply removes its ledger entry', function () {
    $supply = Supply::factory()->create();

    $supply->delete();

    expect(Ledger::query()->count())->toBe(0);
});

test('bookings and supplies are attributed to the authenticated user', function () {
    $manager = User::factory()->marketingManager()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($manager);

    $booking = Booking::query()->create(['customer_id' => $customer->id, 'qty' => 10, 'date' => today()]);
    $supply = Supply::query()->create(['customer_id' => $customer->id, 'qty' => 5, 'date' => today()]);

    expect($booking->user_id)->toBe($manager->id)
        ->and($supply->user_id)->toBe($manager->id)
        ->and((float) $booking->amount)->toBe(0.0);
});

test('booking summary aggregates quantities, amount and balance per customer', function () {
    $customer = Customer::factory()->create();
    Booking::factory()->for($customer)->create(['qty' => 100, 'amount' => 5000, 'date' => '2026-10-01']);
    Booking::factory()->for($customer)->create(['qty' => 50, 'amount' => 2500, 'date' => '2026-10-05']);
    Supply::factory()->for($customer)->create(['qty' => 30, 'date' => '2026-10-10']);
    Customer::factory()->create();

    $summaries = Customer::query()->withBookingSummary()->get();

    expect($summaries)->toHaveCount(1);

    $summary = $summaries->first();

    expect((int) $summary->qty_booked)->toBe(150)
        ->and((float) $summary->amount_paid)->toBe(7500.0)
        ->and((int) $summary->qty_supplied)->toBe(30)
        ->and((int) $summary->balance)->toBe(120)
        ->and(Carbon::parse($summary->latest_date)->toDateString())->toBe('2026-10-10');
});
