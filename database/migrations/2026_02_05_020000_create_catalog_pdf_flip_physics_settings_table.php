<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalog_pdf_flip_physics_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('catalog_pdf_id')
                ->constrained('catalog_pdfs')
                ->cascadeOnDelete();

            $table->string('preset', 50)->default('realistic');

            // Turn.js related tuning
            $table->unsignedInteger('duration_ms')->default(900);
            $table->boolean('gradients')->default(true);
            $table->boolean('acceleration')->default(true);
            $table->unsignedSmallInteger('elevation')->default(50);

            // auto | single | double
            $table->string('display_mode', 10)->default('auto');

            // Optional limits/tuning for preview (client-side rendering)
            $table->unsignedInteger('render_scale_percent')->default(120); // 100-200

            $table->timestamps();

            $table->unique('catalog_pdf_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_pdf_flip_physics_settings');
    }
};
