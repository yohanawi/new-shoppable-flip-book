<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogPdfPage extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'catalog_pdf_id',
        'page_number',
        'display_order',
        'title',
        'is_locked',
        'is_hidden',
        'image_disk',
        'image_path',
        'image_width',
        'image_height',
    ];

    protected $casts = [
        'page_number' => 'integer',
        'display_order' => 'integer',
        'is_locked' => 'boolean',
        'is_hidden' => 'boolean',
        'image_width' => 'integer',
        'image_height' => 'integer',
    ];

    public function pdf(): BelongsTo
    {
        return $this->belongsTo(CatalogPdf::class, 'catalog_pdf_id');
    }
}
