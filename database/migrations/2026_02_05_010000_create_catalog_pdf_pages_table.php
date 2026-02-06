<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalog_pdf_pages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('catalog_pdf_id')->constrained('catalog_pdfs')->cascadeOnDelete();

            // 1-based page index in the source PDF
            $table->unsignedInteger('page_number');

            // Display order used by Page Management
            $table->unsignedInteger('display_order');

            // Human label used in UI (does not modify the original PDF metadata)
            $table->string('title')->nullable();

            $table->boolean('is_locked')->default(false);
            $table->boolean('is_hidden')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['catalog_pdf_id', 'page_number']);
            $table->index(['catalog_pdf_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_pdf_pages');
    }
};
