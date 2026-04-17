<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'stripe_price_id',
        'stripe_product_id',
        'price',
        'currency',
        'interval',
        'trial_days',
        'limits',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'trial_days' => 'integer',
        'limits' => 'array',
        'features' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function hasFeature(string $feature): bool
    {
        return (bool) data_get($this->features ?? [], $feature, false);
    }

    public function limit(string $key): mixed
    {
        return data_get($this->limits ?? [], $key);
    }

    public function isFree(): bool
    {
        return (float) $this->price <= 0;
    }

    public function formattedPrice(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }

        return strtoupper((string) $this->currency) . ' ' . number_format((float) $this->price, 2);
    }
}
