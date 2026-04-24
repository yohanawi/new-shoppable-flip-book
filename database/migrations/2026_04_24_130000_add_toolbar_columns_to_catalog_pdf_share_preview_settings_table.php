<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('catalog_pdf_share_preview_settings', function (Blueprint $table) {
            $table->string('toolbar_background_color', 7)
                ->default('#020617')
                ->after('background_color');
            $table->boolean('toolbar_is_visible')
                ->default(true)
                ->after('toolbar_background_color');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_pdf_share_preview_settings', function (Blueprint $table) {
            $table->dropColumn(['toolbar_background_color', 'toolbar_is_visible']);
        });
    }
};
