<?php

namespace Database\Seeders;

use App\Models\User;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsersSeeder extends Seeder
{
    /** 
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Generator $faker)
    {
        // Make sure roles exist
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'customer']);

        // Demo customer
        $demoCustomer = User::create([
            'name' => $faker->name,
            'email' => 'demo@demo.com',
            'password' => Hash::make('demo'),
            'email_verified_at' => now(),
            'role' => 'customer',
        ]);
        $demoCustomer->assignRole('customer');

        // Demo admin
        $demoAdmin = User::create([
            'name' => $faker->name,
            'email' => 'admin@demo.com',
            'password' => Hash::make('demo'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);
        $demoAdmin->assignRole('admin');
    }
}
