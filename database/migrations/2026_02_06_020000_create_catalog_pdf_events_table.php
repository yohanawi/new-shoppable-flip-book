<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalog_pdf_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('catalog_pdf_id')->constrained('catalog_pdfs')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id', 100)->nullable();

            $table->string('event_type', 50);
            $table->unsignedInteger('page_number')->nullable();
            $table->foreignId('catalog_pdf_hotspot_id')->nullable()->constrained('catalog_pdf_hotspots')->nullOnDelete();

            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['catalog_pdf_id', 'event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_pdf_events');
    }
};
