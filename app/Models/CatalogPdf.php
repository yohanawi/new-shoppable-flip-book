<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CatalogPdf extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'template_type',
        'visibility',
        'storage_disk',
        'pdf_path',
        'original_filename',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public const TEMPLATE_PAGE_MANAGEMENT = 'page_management';
    public const TEMPLATE_FLIP_PHYSICS = 'flip_physics';
    public const TEMPLATE_SLICER_SHOPPABLE = 'slicer_shoppable';

    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PRIVATE = 'private';

    public static function templateTypeOptions(): array
    {
        return [
            self::TEMPLATE_PAGE_MANAGEMENT => 'Page Management',
            self::TEMPLATE_FLIP_PHYSICS => 'Flip Physics',
            self::TEMPLATE_SLICER_SHOPPABLE => 'Slicer (Shoppable)',
        ];
    }

    public static function visibilityOptions(): array
    {
        return [
            self::VISIBILITY_PUBLIC => 'Public',
            self::VISIBILITY_PRIVATE => 'Private',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(CatalogPdfPage::class, 'catalog_pdf_id')->orderBy('display_order');
    }

    public function hotspots(): HasMany
    {
        return $this->hasMany(CatalogPdfHotspot::class, 'catalog_pdf_id')->orderBy('display_order')->orderBy('id');
    }

    public function flipPhysicsSetting(): HasOne
    {
        return $this->hasOne(CatalogPdfFlipPhysicsSetting::class, 'catalog_pdf_id');
    }

    public function isPageManagementTemplate(): bool
    {
        return $this->template_type === self::TEMPLATE_PAGE_MANAGEMENT;
    }

    public function isSlicerTemplate(): bool
    {
        return $this->template_type === self::TEMPLATE_SLICER_SHOPPABLE;
    }

    public function storagePath(): string
    {
        return Storage::disk($this->storage_disk)->path($this->pdf_path);
    }
}
