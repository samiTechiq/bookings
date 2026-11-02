<?php

use App\Enums\UserRole;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Supplies\Pages\CreateSupply;
use App\Filament\Resources\Supplies\SupplyResource;
use App\Filament\Resources\SupplySchedules\Pages\CreateSupplySchedule;
use App\Filament\Resources\SupplySchedules\SupplyScheduleResource;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\Customer;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->rootAdmin()->create());
});

test('creating a customer returns to the customer list', function () {
    livewire(CreateCustomer::class)
        ->fillForm(['name' => 'New Customer'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(CustomerResource::getUrl('index'));
});

test('creating a supply returns to the supply list', function () {
    livewire(CreateSupply::class)
        ->fillForm([
            'customer_id' => Customer::factory()->create()->id,
            'date' => today()->toDateString(),
            'qty' => 10,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(SupplyResource::getUrl('index'));
});

test('creating a supply schedule returns to the schedule list', function () {
    livewire(CreateSupplySchedule::class)
        ->fillForm(['date' => today()->addDay()->toDateString()])
        ->set('data.items', [['customer_id' => Customer::factory()->create()->id, 'qty' => 10]])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(SupplyScheduleResource::getUrl('index'));
});

test('creating a user returns to the user list', function () {
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'New Manager',
            'email' => 'new.manager@example.com',
            'password' => 'secret-password',
            'role' => UserRole::MarketingManager->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect(UserResource::getUrl('index'));
});
