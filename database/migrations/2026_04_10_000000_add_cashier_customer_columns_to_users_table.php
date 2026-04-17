<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'stripe_id')) {
                $table->string('stripe_id')->nullable()->index()->after('avatar');
            }

            if (!Schema::hasColumn('users', 'pm_type')) {
                $table->string('pm_type')->nullable()->after('stripe_id');
            }

            if (!Schema::hasColumn('users', 'pm_last_four')) {
                $table->string('pm_last_four', 4)->nullable()->after('pm_type');
            }

            if (!Schema::hasColumn('users', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('pm_last_four');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('users', 'stripe_id')) {
                $table->dropIndex(['stripe_id']);
                $columns[] = 'stripe_id';
            }

            if (Schema::hasColumn('users', 'pm_type')) {
                $columns[] = 'pm_type';
            }

            if (Schema::hasColumn('users', 'pm_last_four')) {
                $columns[] = 'pm_last_four';
            }

            if (Schema::hasColumn('users', 'trial_ends_at')) {
                $columns[] = 'trial_ends_at';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
