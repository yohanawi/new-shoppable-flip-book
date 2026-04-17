<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingInvoice extends Model
{
    use HasFactory;

    protected $table = 'invoices';

    protected $fillable = [
        'user_id',
        'subscription_id',
        'stripe_invoice_id',
        'number',
        'currency',
        'amount_due',
        'amount_paid',
        'subtotal',
        'tax',
        'status',
        'invoice_pdf_url',
        'hosted_invoice_url',
        'period_start',
        'period_end',
        'paid_at',
        'meta',
    ];

    protected $casts = [
        'amount_due' => 'integer',
        'amount_paid' => 'integer',
        'subtotal' => 'integer',
        'tax' => 'integer',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BillingTransaction::class, 'invoice_id');
    }
}
