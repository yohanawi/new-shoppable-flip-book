<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogPdfEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'catalog_pdf_id',
        'user_id',
        'session_id',
        'event_type',
        'page_number',
        'catalog_pdf_hotspot_id',
        'meta',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];
}
