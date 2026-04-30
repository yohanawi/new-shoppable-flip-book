<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Address;
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
        $this->call([
            ProjectPermissionsSeeder::class,
            UsersSeeder::class,
            // BillingSeeder::class,
        ]);

        // if (app()->environment('local')) {
        //     $this->call([
        //         BillingDemoSeeder::class,
        //     ]);
        // }

        Address::factory(2)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
