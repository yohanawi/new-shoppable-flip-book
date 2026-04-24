<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CatalogPdfSharePreviewSetting extends Model
{
    use HasFactory;

    public const BACKGROUND_COLOR = 'color';
    public const BACKGROUND_IMAGE = 'image';
    public const BACKGROUND_VIDEO = 'video';

    protected $table = 'catalog_pdf_share_preview_settings';

    protected $fillable = [
        'catalog_pdf_id',
        'background_type',
        'background_color',
        'toolbar_background_color',
        'toolbar_is_visible',
        'background_image_disk',
        'background_image_path',
        'background_image_mime',
        'background_video_disk',
        'background_video_path',
        'background_video_mime',
        'logo_disk',
        'logo_path',
        'logo_mime',
        'logo_title',
        'logo_position_x',
        'logo_position_y',
        'logo_width',
    ];

    protected $casts = [
        'toolbar_is_visible' => 'boolean',
        'logo_position_x' => 'integer',
        'logo_position_y' => 'integer',
        'logo_width' => 'integer',
    ];

    public static function backgroundTypeOptions(): array
    {
        return [
            self::BACKGROUND_COLOR => 'Solid color',
            self::BACKGROUND_IMAGE => 'Background image',
            self::BACKGROUND_VIDEO => 'Background video',
        ];
    }

    public static function defaultBackgroundType(): string
    {
        return self::BACKGROUND_COLOR;
    }

    public static function defaultBackgroundColor(): string
    {
        return '#0F172A';
    }

    public static function defaultToolbarBackgroundColor(): string
    {
        return '#020617';
    }

    public static function defaultToolbarVisibility(): bool
    {
        return true;
    }

    public static function defaultLogoPositionX(): int
    {
        return 8;
    }

    public static function defaultLogoPositionY(): int
    {
        return 8;
    }

    public static function defaultLogoWidth(): int
    {
        return 168;
    }

    public function pdf(): BelongsTo
    {
        return $this->belongsTo(CatalogPdf::class, 'catalog_pdf_id');
    }

    public function applyDefaults(): void
    {
        $this->fill([
            'background_type' => self::defaultBackgroundType(),
            'background_color' => self::defaultBackgroundColor(),
            'toolbar_background_color' => self::defaultToolbarBackgroundColor(),
            'toolbar_is_visible' => self::defaultToolbarVisibility(),
            'logo_position_x' => self::defaultLogoPositionX(),
            'logo_position_y' => self::defaultLogoPositionY(),
            'logo_width' => self::defaultLogoWidth(),
        ]);
    }

    public function hasBackgroundImage(): bool
    {
        return $this->assetExists($this->background_image_disk, $this->background_image_path);
    }

    public function hasBackgroundVideo(): bool
    {
        return $this->assetExists($this->background_video_disk, $this->background_video_path);
    }

    public function hasLogo(): bool
    {
        return $this->assetExists($this->logo_disk, $this->logo_path);
    }

    public function effectiveBackgroundType(): string
    {
        $backgroundType = $this->background_type ?: self::defaultBackgroundType();

        if ($backgroundType === self::BACKGROUND_IMAGE && !$this->hasBackgroundImage()) {
            return self::BACKGROUND_COLOR;
        }

        if ($backgroundType === self::BACKGROUND_VIDEO && !$this->hasBackgroundVideo()) {
            return self::BACKGROUND_COLOR;
        }

        return $backgroundType;
    }

    public function appearanceSettings(): array
    {
        return [
            'backgroundType' => $this->effectiveBackgroundType(),
            'backgroundColor' => (string) ($this->background_color ?: self::defaultBackgroundColor()),
            'toolbarBackgroundColor' => (string) ($this->toolbar_background_color ?: self::defaultToolbarBackgroundColor()),
            'toolbarVisible' => (bool) ($this->toolbar_is_visible ?? self::defaultToolbarVisibility()),
            'logoTitle' => (string) ($this->logo_title ?? ''),
            'logoPositionX' => (int) ($this->logo_position_x ?? self::defaultLogoPositionX()),
            'logoPositionY' => (int) ($this->logo_position_y ?? self::defaultLogoPositionY()),
            'logoWidth' => (int) ($this->logo_width ?? self::defaultLogoWidth()),
        ];
    }

    public function assetFor(string $asset): ?array
    {
        return match ($asset) {
            'background-image' => $this->background_image_path ? [
                'disk' => $this->background_image_disk ?: 'local',
                'path' => $this->background_image_path,
                'mime' => $this->background_image_mime,
            ] : null,
            'background-video' => $this->background_video_path ? [
                'disk' => $this->background_video_disk ?: 'local',
                'path' => $this->background_video_path,
                'mime' => $this->background_video_mime,
            ] : null,
            'logo' => $this->logo_path ? [
                'disk' => $this->logo_disk ?: 'local',
                'path' => $this->logo_path,
                'mime' => $this->logo_mime,
            ] : null,
            default => null,
        };
    }

    private function assetExists(?string $disk, ?string $path): bool
    {
        if (!filled($path)) {
            return false;
        }

        return Storage::disk($disk ?: 'local')->exists($path);
    }
}
