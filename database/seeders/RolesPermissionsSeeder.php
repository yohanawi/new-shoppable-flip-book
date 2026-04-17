<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(ProjectPermissionsSeeder::class);
    }
}
