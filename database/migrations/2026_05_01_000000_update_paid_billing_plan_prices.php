<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')
            ->where('slug', 'basic')
            ->update([
                'price' => 10,
                'trial_days' => null,
                'updated_at' => now(),
            ]);

        DB::table('plans')
            ->where('slug', 'pro')
            ->update([
                'price' => 20,
                'trial_days' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('plans')
            ->where('slug', 'basic')
            ->update([
                'price' => 29,
                'trial_days' => 14,
                'updated_at' => now(),
            ]);

        DB::table('plans')
            ->where('slug', 'pro')
            ->update([
                'price' => 99,
                'trial_days' => 14,
                'updated_at' => now(),
            ]);
    }
};
