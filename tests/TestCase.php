<?php

namespace Tests;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed the application roles before each test that refreshes the database.
     */
    protected bool $seed = true;

    /**
     * @var class-string
     */
    protected string $seeder = RoleSeeder::class;
}
