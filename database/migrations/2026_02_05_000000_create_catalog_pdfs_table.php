<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalog_pdfs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // page_management | flip_physics | slicer_shoppable
            $table->string('template_type', 50);

            // public | private
            $table->string('visibility', 20)->default('private');

            // storage metadata
            $table->string('storage_disk', 20)->default('local');
            $table->string('pdf_path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->timestamps();

            $table->index(['template_type', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_pdfs');
    }
};
