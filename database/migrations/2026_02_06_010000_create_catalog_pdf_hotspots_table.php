<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_pdf_hotspots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('catalog_pdf_id')->constrained('catalog_pdfs')->cascadeOnDelete();
            $table->foreignId('catalog_pdf_page_id')->constrained('catalog_pdf_pages')->cascadeOnDelete();

            $table->unsignedInteger('display_order')->default(0);

            // Drawing tool
            $table->string('shape_type'); // rectangle | polygon | free
            $table->json('shape_data');   // fabric.js object JSON

            // Normalized bounding box (0..1) relative to background image size
            $table->decimal('x', 8, 6);
            $table->decimal('y', 8, 6);
            $table->decimal('w', 8, 6);
            $table->decimal('h', 8, 6);

            // Action
            $table->string('action_type'); // internal_page | external_link | popup_window | popup_image | popup_video
            $table->boolean('is_active')->default(true);

            // Common display fields
            $table->string('title')->nullable();
            $table->string('color')->nullable();
            $table->string('thumbnail_disk')->nullable();
            $table->string('thumbnail_path')->nullable();

            // Link-style actions
            $table->string('link')->nullable();
            $table->unsignedInteger('internal_page_number')->nullable();

            // Product-like popup window
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();

            // Popup image
            $table->string('popup_image_disk')->nullable();
            $table->string('popup_image_path')->nullable();

            // Popup video
            $table->string('popup_video_disk')->nullable();
            $table->string('popup_video_path')->nullable();
            $table->string('popup_video_url')->nullable();

            $table->timestamps();

            $table->index(['catalog_pdf_id', 'catalog_pdf_page_id']);
            $table->index(['catalog_pdf_id', 'action_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_pdf_hotspots');
    }
};
