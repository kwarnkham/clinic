<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        User::factory()->create([
            'name' => 'admin',
            'username' => 'admin',
            'password' => bcrypt('password')
        ]);

        Role::create([
            'name' => 'admin',
        ]);

        Role::create([
            'name' => 'receptionist',
        ]);

        Role::create([
            'name' => 'cashier',
        ]);

        Role::create([
            'name' => 'pharmacist',
        ]);

        Role::all()->each(fn ($role) => $role->users()->attach(1));
    }
}
