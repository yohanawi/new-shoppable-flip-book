<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogPdfHotspot extends Model
{
    use HasFactory;

    public const SHAPE_RECTANGLE = 'rectangle';
    public const SHAPE_POLYGON = 'polygon';
    public const SHAPE_FREE = 'free';

    public const ACTION_INTERNAL_PAGE = 'internal_page';
    public const ACTION_EXTERNAL_LINK = 'external_link';
    public const ACTION_POPUP_WINDOW = 'popup_window';
    public const ACTION_POPUP_IMAGE = 'popup_image';
    public const ACTION_POPUP_VIDEO = 'popup_video';

    protected $fillable = [
        'catalog_pdf_id',
        'catalog_pdf_page_id',
        'display_order',
        'shape_type',
        'shape_data',
        'x',
        'y',
        'w',
        'h',
        'action_type',
        'is_active',
        'title',
        'color',
        'thumbnail_disk',
        'thumbnail_path',
        'link',
        'internal_page_number',
        'description',
        'price',
        'popup_image_disk',
        'popup_image_path',
        'popup_video_disk',
        'popup_video_path',
        'popup_video_url',
    ];

    protected $casts = [
        'shape_data' => 'array',
        'x' => 'float',
        'y' => 'float',
        'w' => 'float',
        'h' => 'float',
        'display_order' => 'integer',
        'is_active' => 'boolean',
        'internal_page_number' => 'integer',
        'price' => 'decimal:2',
    ];

    public static function shapeOptions(): array
    {
        return [
            self::SHAPE_RECTANGLE => 'Rectangle',
            self::SHAPE_POLYGON => 'Polygon',
            self::SHAPE_FREE => 'Free',
        ];
    }

    public static function actionOptions(): array
    {
        return [
            self::ACTION_INTERNAL_PAGE => 'Internal Page',
            self::ACTION_EXTERNAL_LINK => 'External Link',
            self::ACTION_POPUP_WINDOW => 'Popup Window',
            self::ACTION_POPUP_IMAGE => 'Popup an Image',
            self::ACTION_POPUP_VIDEO => 'Popup a Video',
        ];
    }

    public function pdf(): BelongsTo
    {
        return $this->belongsTo(CatalogPdf::class, 'catalog_pdf_id');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(CatalogPdfPage::class, 'catalog_pdf_page_id');
    }
}
