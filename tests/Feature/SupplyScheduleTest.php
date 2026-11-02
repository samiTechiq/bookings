<?php

use App\Filament\Resources\SupplySchedules\Pages\CreateSupplySchedule;
use App\Filament\Resources\SupplySchedules\Pages\EditSupplySchedule;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\SupplySchedule;
use App\Models\SupplyScheduleItem;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->manager = User::factory()->marketingManager()->create();
    $this->actingAs($this->manager);
});

test('managers can access the supply schedule pages', function (string $url) {
    $this->get($url)->assertSuccessful();
})->with([
    'list' => '/admin/supply-schedules',
    'create' => '/admin/supply-schedules/create',
]);

test('manager creates a schedule for one date with multiple customers', function () {
    [$first, $second] = Customer::factory()->count(2)->create();
    $date = today()->addDay()->toDateString();

    livewire(CreateSupplySchedule::class)
        ->fillForm(['date' => $date])
        ->set('data.items', [
            ['customer_id' => $first->id, 'qty' => 120],
            ['customer_id' => $second->id, 'qty' => 80],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $schedule = SupplySchedule::query()->sole();

    expect($schedule->date->toDateString())->toBe($date)
        ->and($schedule->user_id)->toBe($this->manager->id)
        ->and($schedule->items()->pluck('qty', 'customer_id')->all())
        ->toEqual([$first->id => 120, $second->id => 80]);
});

test('the same customer cannot be added twice to a schedule', function () {
    $customer = Customer::factory()->create();

    livewire(CreateSupplySchedule::class)
        ->fillForm(['date' => today()->addDay()->toDateString()])
        ->set('data.items', [
            ['customer_id' => $customer->id, 'qty' => 10],
            ['customer_id' => $customer->id, 'qty' => 20],
        ])
        ->call('create')
        ->assertHasFormErrors();

    expect(SupplySchedule::query()->count())->toBe(0);
});

test('only one schedule can exist per date', function () {
    $existing = SupplySchedule::factory()->create(['date' => today()->addDay()]);
    SupplyScheduleItem::factory()->for($existing, 'schedule')->create();
    $customer = Customer::factory()->create();

    livewire(CreateSupplySchedule::class)
        ->fillForm(['date' => today()->addDay()->toDateString()])
        ->set('data.items', [['customer_id' => $customer->id, 'qty' => 10]])
        ->call('create')
        ->assertHasFormErrors(['date']);

    livewire(EditSupplySchedule::class, ['record' => $existing->id])
        ->call('save')
        ->assertHasNoFormErrors();
});

test('load pending customers fills the list with outstanding balances', function () {
    $pending = Customer::factory()->create();
    Booking::factory()->for($pending)->create(['qty' => 90]);
    Customer::factory()->create();

    $component = livewire(CreateSupplySchedule::class)
        ->callAction([['name' => 'loadPendingCustomers', 'context' => ['schemaComponent' => 'form.items']]]);

    expect(array_values($component->get('data.items')))
        ->toEqual([['customer_id' => $pending->id, 'qty' => 90]]);
});

test('report includes the next upcoming schedule', function () {
    $customer = Customer::factory()->create(['name' => 'Scheduled Customer']);
    $schedule = SupplySchedule::factory()->create(['date' => today()->addDay()]);
    SupplyScheduleItem::factory()->for($schedule, 'schedule')->for($customer)->create(['qty' => 55]);

    $this->get(route('reports.daily'))
        ->assertSuccessful()
        ->assertDownload('bottle_booking_sheet_'.today()->toDateString().'.pdf');
});
