<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable');
            $table->string('notification_type');
            $table->string('unique_key');
            $table->json('context')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(
                ['notifiable_type', 'notifiable_id', 'notification_type', 'unique_key'],
                'notification_deliveries_unique_notification'
            );
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
