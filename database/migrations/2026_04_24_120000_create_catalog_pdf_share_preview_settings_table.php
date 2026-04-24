<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalog_pdf_share_preview_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('catalog_pdf_id')
                ->constrained('catalog_pdfs')
                ->cascadeOnDelete();

            $table->string('background_type', 20)->default('color');
            $table->string('background_color', 7)->default('#0F172A');

            $table->string('background_image_disk', 20)->nullable();
            $table->string('background_image_path')->nullable();
            $table->string('background_image_mime', 120)->nullable();

            $table->string('background_video_disk', 20)->nullable();
            $table->string('background_video_path')->nullable();
            $table->string('background_video_mime', 120)->nullable();

            $table->string('logo_disk', 20)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('logo_mime', 120)->nullable();
            $table->string('logo_title', 120)->nullable();
            $table->unsignedSmallInteger('logo_position_x')->default(8);
            $table->unsignedSmallInteger('logo_position_y')->default(8);
            $table->unsignedSmallInteger('logo_width')->default(168);

            $table->timestamps();

            $table->unique('catalog_pdf_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_pdf_share_preview_settings');
    }
};
