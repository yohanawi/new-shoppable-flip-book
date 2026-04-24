<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('support_ticket_categories')->insert([
            [
                'name' => 'Technical',
                'slug' => 'technical',
                'description' => 'Technical issues, bugs, and product functionality questions.',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Payment',
                'slug' => 'payment',
                'description' => 'Billing, invoices, refunds, and payment method support.',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'General',
                'slug' => 'general',
                'description' => 'General product questions and non-technical assistance.',
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->foreignId('support_ticket_category_id')
                ->nullable()
                ->after('user_id')
                ->constrained('support_ticket_categories')
                ->nullOnDelete();
            $table->string('attachment_path')->nullable()->after('message');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            $table->unsignedTinyInteger('feedback_rating')->nullable()->after('attachment_name');
            $table->text('feedback_comment')->nullable()->after('feedback_rating');
            $table->timestamp('closed_at')->nullable()->after('feedback_comment');
        });

        $categoryIds = DB::table('support_ticket_categories')
            ->pluck('id', 'slug');

        $legacyMap = [
            'billing' => 'payment',
            'payment' => 'payment',
            'technical' => 'technical',
            'general' => 'general',
        ];

        foreach ($legacyMap as $legacyCategory => $newSlug) {
            $categoryId = $categoryIds[$newSlug] ?? null;

            if ($categoryId === null) {
                continue;
            }

            DB::table('support_tickets')
                ->where('category', $legacyCategory)
                ->update(['support_ticket_category_id' => $categoryId]);
        }
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('support_ticket_category_id');
            $table->dropColumn([
                'attachment_path',
                'attachment_name',
                'feedback_rating',
                'feedback_comment',
                'closed_at',
            ]);
        });

        Schema::dropIfExists('support_ticket_categories');
    }
};
