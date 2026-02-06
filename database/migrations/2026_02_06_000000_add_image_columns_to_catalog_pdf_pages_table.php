<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_pdf_pages', function (Blueprint $table) {
            $table->string('image_disk')->nullable()->after('is_hidden');
            $table->string('image_path')->nullable()->after('image_disk');
            $table->unsignedInteger('image_width')->nullable()->after('image_path');
            $table->unsignedInteger('image_height')->nullable()->after('image_width');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_pdf_pages', function (Blueprint $table) {
            $table->dropColumn(['image_disk', 'image_path', 'image_width', 'image_height']);
        });
    }
};
