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
        $user = User::factory()->create([
            'name' => 'admin',
            'username' => 'admin',
            'password' => bcrypt('password')
        ]);

        $roles = ['admin', 'receptionist', 'cashier', 'pharmacist'];

        $roles = array_map(fn ($role) => Role::create(['name' => $role]), $roles);

        collect($roles)->each(function ($role) use ($user) {
            $role->users()->attach($user->id);
        });
    }
}
