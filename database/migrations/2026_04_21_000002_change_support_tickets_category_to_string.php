<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE support_tickets MODIFY category VARCHAR(100) NOT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('support_tickets')
            ->whereNotIn('category', ['technical', 'billing', 'general'])
            ->update(['category' => 'general']);

        DB::statement("ALTER TABLE support_tickets MODIFY category ENUM('technical','billing','general') NOT NULL");
    }
};
