<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $time = now();
        DB::table('users')->insert([
            [
                'name' => 'admin',
                'username' => 'admin',
                'password' => bcrypt('password'),
                'created_at' => $time,
                'updated_at' => $time,
            ],
        ]);
        DB::table('visit_types')->insert([
            [
                'name' => '1',
                'created_at' => $time,
                'updated_at' => $time,
            ],
        ]);
        $user = User::first();

        $roles = ['admin', 'receptionist', 'cashier', 'pharmacist'];

        $roles = array_map(fn ($role) => Role::create(['name' => $role]), $roles);

        collect($roles)->each(function ($role) use ($user) {
            $role->users()->attach($user->id);
        });

        $item = Item::create([
            'name' => 'Non stocked item',
            'description' => 'This item is not applicable as stocked item',
            'type' => ItemType::NON_STOCKED->value,
        ]);

        $item->products()->create([
            'name' => 'Record book',
            'description' => 'Record book',
            'sale_price' => 1000,
        ]);
    }
}
