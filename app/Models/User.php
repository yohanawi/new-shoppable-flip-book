<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use Billable;
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable. 
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'role',
        'avatar',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'last_login_at',
        'last_login_ip',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo_path) {
            return asset('storage/' . $this->profile_photo_path);
        }

        return $this->profile_photo_path;
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function getDefaultAddressAttribute()
    {
        return $this->addresses?->first();
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function catalogPdfs(): HasMany
    {
        return $this->hasMany(CatalogPdf::class);
    }

    public function billingInvoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class, 'user_id')->latest('created_at');
    }

    public function billingTransactions(): HasMany
    {
        return $this->hasMany(BillingTransaction::class, 'user_id')->latest('created_at');
    }

    public function isAdmin(): bool
    {
        return strcasecmp((string) $this->role, 'admin') === 0
            || $this->hasRole('Admin')
            || $this->hasRole('admin');
    }

    public function isCustomer(): bool
    {
        return strcasecmp((string) $this->role, 'customer') === 0
            || $this->hasRole('Customer')
            || $this->hasRole('customer');
    }
}
