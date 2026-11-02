<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $rootAdmin = User::query()->firstOrCreate(
            ['email' => 'root@gmail.com'],
            ['name' => 'Root', 'password' => 'password'],
        );

        $rootAdmin->assignRole(UserRole::RootAdmin);
    }
}
