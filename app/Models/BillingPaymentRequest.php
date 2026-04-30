<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class BillingPaymentRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const GATEWAY_MANUAL = 'manual';
    public const GATEWAY_STRIPE = 'stripe';
    public const GATEWAY_PAYPAL = 'paypal';
    public const GATEWAY_PAYHERE = 'payhere';

    protected $fillable = [
        'user_id',
        'plan_id',
        'subscription_id',
        'invoice_id',
        'reviewed_by',
        'gateway',
        'currency',
        'amount',
        'transaction_reference',
        'receipt_disk',
        'receipt_path',
        'receipt_name',
        'status',
        'customer_note',
        'admin_note',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'rejected_at',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'meta' => 'array',
    ];

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_UNDER_REVIEW,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'invoice_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_UNDER_REVIEW], true);
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function requestNumber(): string
    {
        return 'BPR-' . str_pad((string) $this->getKey(), 6, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function gatewayLabel(): string
    {
        return match ((string) $this->gateway) {
            self::GATEWAY_MANUAL, '' => 'Manual payment',
            self::GATEWAY_STRIPE => 'Stripe',
            self::GATEWAY_PAYPAL => 'PayPal',
            self::GATEWAY_PAYHERE => 'PayHere',
            default => ucfirst(str_replace('_', ' ', (string) $this->gateway)),
        };
    }

    public function receiptUrl(): ?string
    {
        if (blank($this->receipt_path) || blank($this->receipt_disk)) {
            return null;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->receipt_disk);

        return $disk->url($this->receipt_path);
    }

    public function hasReceipt(): bool
    {
        if (blank($this->receipt_path) || blank($this->receipt_disk)) {
            return false;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($this->receipt_disk);

        return $disk->exists($this->receipt_path);
    }
}
