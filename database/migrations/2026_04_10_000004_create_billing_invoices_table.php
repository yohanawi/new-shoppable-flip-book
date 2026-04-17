<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('stripe_invoice_id')->nullable()->unique();
            $table->string('number')->nullable();
            $table->string('currency', 3)->default('usd');
            $table->integer('amount_due')->default(0);
            $table->integer('amount_paid')->default(0);
            $table->integer('subtotal')->nullable();
            $table->integer('tax')->nullable();
            $table->string('status')->default('draft');
            $table->text('invoice_pdf_url')->nullable();
            $table->text('hosted_invoice_url')->nullable();
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['subscription_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
