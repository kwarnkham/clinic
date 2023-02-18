<?php

namespace Tests;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    protected $admin;
    protected $recepitonist;
    protected $cashier;

    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::first();
        $recepitonist = User::factory()->create();
        $recepitonist->roles()->attach(
            Role::where('name', RoleName::RECEPTIONIST->value)->first()
        );
        $this->recepitonist = $recepitonist;

        $cashier = User::factory()->create();
        $cashier->roles()->attach(
            Role::where('name', RoleName::CASHIER->value)->first()
        );
        $this->cashier = $cashier;
    }
}
