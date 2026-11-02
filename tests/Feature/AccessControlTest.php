<?php

use App\Models\User;

test('marketing managers can access business pages', function (string $url) {
    $this->actingAs(User::factory()->marketingManager()->create())
        ->get($url)
        ->assertSuccessful();
})->with([
    'bookings' => '/admin',
    'customers' => '/admin/customers',
    'supplies' => '/admin/supplies',
    'create supply' => '/admin/supplies/create',
]);

test('marketing managers are locked out of user management', function (string $url) {
    $this->actingAs(User::factory()->marketingManager()->create())
        ->get($url)
        ->assertForbidden();
})->with([
    'list users' => '/admin/users',
    'create user' => '/admin/users/create',
]);

test('marketing managers do not see user management in navigation', function () {
    $this->actingAs(User::factory()->marketingManager()->create())
        ->get('/admin')
        ->assertSuccessful()
        ->assertDontSee('/admin/users');
});

test('root admins can access user management', function () {
    $this->actingAs(User::factory()->rootAdmin()->create())
        ->get('/admin/users')
        ->assertSuccessful();
});

test('users without a role cannot access the panel', function () {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertForbidden();
});
