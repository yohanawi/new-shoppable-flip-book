<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class CatalogPdfEvent extends Model
{
    public const EVENT_BOOK_OPEN = 'book_open';
    public const EVENT_PAGE_VIEW = 'page_view';
    public const EVENT_READING_TIME = 'reading_time';
    public const EVENT_HOTSPOT_CLICK = 'hotspot_click';

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

    public static function trackedEventTypes(): array
    {
        return [
            self::EVENT_BOOK_OPEN,
            self::EVENT_PAGE_VIEW,
            self::EVENT_READING_TIME,
            self::EVENT_HOTSPOT_CLICK,
        ];
    }

    public function pdf(): BelongsTo
    {
        return $this->belongsTo(CatalogPdf::class, 'catalog_pdf_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hotspot(): BelongsTo
    {
        return $this->belongsTo(CatalogPdfHotspot::class, 'catalog_pdf_hotspot_id');
    }
}
