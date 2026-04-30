<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Subscription as CashierSubscription;

class Subscription extends CashierSubscription
{
    protected $fillable = [
        'user_id',
        'type',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'quantity',
        'trial_ends_at',
        'ends_at',
        'plan_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function invoiceRecords(): HasMany
    {
        return $this->hasMany(BillingInvoice::class, 'subscription_id');
    }

    public function transactionRecords(): HasMany
    {
        return $this->hasMany(BillingTransaction::class, 'subscription_id');
    }

    public function isManualBilling(): bool
    {
        return str_starts_with((string) $this->stripe_id, 'manual_payment_request_');
    }

    public function manualPeriodEnd(): CarbonInterface
    {
        $invoice = $this->invoiceRecords()
            ->whereNotNull('period_end')
            ->latest('period_end')
            ->first();

        if ($invoice?->period_end && $invoice->period_end->isFuture()) {
            return $invoice->period_end->copy();
        }

        return now();
    }

    public function cancelManual(): void
    {
        $this->forceFill([
            'ends_at' => $this->manualPeriodEnd(),
            'stripe_status' => 'active',
        ])->save();
    }

    public function resumeManual(): void
    {
        $this->forceFill([
            'ends_at' => null,
            'stripe_status' => 'active',
        ])->save();
    }
}
