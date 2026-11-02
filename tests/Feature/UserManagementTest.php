<?php

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->rootAdmin = User::factory()->rootAdmin()->create();
    $this->actingAs($this->rootAdmin);
});

test('root admin can create a marketing manager', function () {
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Ada Manager',
            'email' => 'ada@example.com',
            'password' => 'secret-password',
            'role' => UserRole::MarketingManager->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $user = User::query()->where('email', 'ada@example.com')->sole();

    expect($user->hasRole(UserRole::MarketingManager))->toBeTrue()
        ->and($user->isRootAdmin())->toBeFalse();
});

test('editing a user without a password keeps the existing password', function () {
    $manager = User::factory()->marketingManager()->create();
    $originalHash = $manager->password;

    livewire(EditUser::class, ['record' => $manager->id])
        ->assertSchemaStateSet(['role' => UserRole::MarketingManager])
        ->fillForm(['name' => 'Renamed', 'password' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($manager->fresh()->name)->toBe('Renamed')
        ->and($manager->fresh()->password)->toBe($originalHash);
});

test('root admin cannot change their own role', function () {
    livewire(EditUser::class, ['record' => $this->rootAdmin->id])
        ->assertFormFieldDisabled('role');
});
