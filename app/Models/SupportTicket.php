<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'support_ticket_category_id',
        'subject',
        'category',
        'priority',
        'status',
        'message',
        'attachment_path',
        'attachment_name',
        'feedback_rating',
        'feedback_comment',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
        'feedback_rating' => 'integer',
    ];

    /* =====================
        Relationships
    ===================== */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categoryRelation()
    {
        return $this->belongsTo(SupportTicketCategory::class, 'support_ticket_category_id');
    }

    public function messages()
    {
        return $this->hasMany(SupportTicketMessage::class);
    }

    protected function categoryName(): Attribute
    {
        return Attribute::get(function () {
            if ($this->relationLoaded('categoryRelation') || $this->support_ticket_category_id !== null) {
                $name = $this->categoryRelation?->name;

                if (filled($name)) {
                    return $name;
                }
            }

            return ucfirst(str_replace('_', ' ', (string) $this->category));
        });
    }

    protected function attachmentUrl(): Attribute
    {
        return Attribute::get(function () {
            if (blank($this->attachment_path)) {
                return null;
            }

            return asset('storage/' . ltrim((string) $this->attachment_path, '/'));
        });
    }
}
