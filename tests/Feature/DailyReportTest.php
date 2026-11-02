<?php

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Supply;
use App\Models\User;

test('managers can download the end of day report', function () {
    $pending = Customer::factory()->create(['name' => 'Pending Customer']);
    Booking::factory()->for($pending)->create(['qty' => 100]);
    Supply::factory()->for($pending)->create(['qty' => 40, 'date' => today()]);

    $response = $this->actingAs(User::factory()->marketingManager()->create())
        ->get(route('reports.daily'));

    $response->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload('bottle_booking_sheet_'.today()->toDateString().'.pdf');
});

test('report lists pending balances and today\'s supplies', function () {
    $pending = Customer::factory()->create(['name' => 'Pending Customer']);
    Booking::factory()->for($pending)->create(['qty' => 100]);
    Supply::factory()->for($pending)->create(['qty' => 40, 'date' => today()]);

    $settled = Customer::factory()->create(['name' => 'Settled Customer']);
    Booking::factory()->for($settled)->create(['qty' => 20]);
    Supply::factory()->for($settled)->create(['qty' => 20, 'date' => today()->subDay()]);

    $this->actingAs(User::factory()->marketingManager()->create());

    $view = $this->view('reports.daily-booking-sheet', [
        'date' => today(),
        'generatedAt' => now(),
        'generatedBy' => auth()->user(),
        'pendingSupplies' => Customer::query()->withPendingBalance()->get(),
        'todaysSupplies' => Customer::query()->withBookingSummary()
            ->whereHas('supplies', fn ($query) => $query->whereDate('date', today()))
            ->withSum(['supplies as qty_supplied_today' => fn ($query) => $query->whereDate('date', today())], 'qty')
            ->get(),
        'proposedSchedule' => null,
        'todaysSupplyTotal' => 40,
    ]);

    $view->assertSeeInOrder(['1. Pending Supplies', '2. Proposed Supply Schedule', '3. Today&#039;s Supplies'], escape: false)
        ->assertSee('NATU FOOD AND BEVERAGE LIMITED')
        ->assertSee('Pending Customer')
        ->assertDontSee('Settled Customer');
});

test('guests are redirected to login', function () {
    $this->get(route('reports.daily'))->assertRedirect(route('filament.admin.auth.login'));
});

test('users without a role cannot download the report', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('reports.daily'))
        ->assertForbidden();
});
