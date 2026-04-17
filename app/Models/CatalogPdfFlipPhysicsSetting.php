<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogPdfFlipPhysicsSetting extends Model
{
    use HasFactory;

    private const DEFAULT_DISPLAY_MODE = 'auto';

    protected $table = 'catalog_pdf_flip_physics_settings';

    protected $fillable = [
        'catalog_pdf_id',
        'preset',
        'duration_ms',
        'gradients',
        'acceleration',
        'elevation',
        'display_mode',
        'render_scale_percent',
    ];

    protected $casts = [
        'duration_ms' => 'integer',
        'gradients' => 'boolean',
        'acceleration' => 'boolean',
        'elevation' => 'integer',
        'render_scale_percent' => 'integer',
    ];

    public const PRESET_REALISTIC = 'realistic';
    public const PRESET_SNAPPY = 'snappy';
    public const PRESET_SMOOTH = 'smooth';
    public const PRESET_MINIMAL = 'minimal';

    public static function defaultPreset(): string
    {
        return self::PRESET_REALISTIC;
    }

    public static function presetOptions(): array
    {
        return [
            self::PRESET_REALISTIC => 'Realistic (default)',
            self::PRESET_SNAPPY => 'Snappy (fast)',
            self::PRESET_SMOOTH => 'Smooth (soft)',
            self::PRESET_MINIMAL => 'Minimal (lightweight)',
        ];
    }

    public static function presetDefaults(): array
    {
        return [
            self::PRESET_REALISTIC => [
                'duration_ms' => 900,
                'gradients' => true,
                'acceleration' => true,
                'elevation' => 50,
                'display_mode' => self::DEFAULT_DISPLAY_MODE,
                'render_scale_percent' => 120,
            ],
            self::PRESET_SNAPPY => [
                'duration_ms' => 550,
                'gradients' => true,
                'acceleration' => true,
                'elevation' => 30,
                'display_mode' => self::DEFAULT_DISPLAY_MODE,
                'render_scale_percent' => 110,
            ],
            self::PRESET_SMOOTH => [
                'duration_ms' => 1200,
                'gradients' => true,
                'acceleration' => true,
                'elevation' => 70,
                'display_mode' => self::DEFAULT_DISPLAY_MODE,
                'render_scale_percent' => 140,
            ],
            self::PRESET_MINIMAL => [
                'duration_ms' => 700,
                'gradients' => false,
                'acceleration' => false,
                'elevation' => 10,
                'display_mode' => self::DEFAULT_DISPLAY_MODE,
                'render_scale_percent' => 100,
            ],
        ];
    }

    public static function attributesForPreset(string $preset): array
    {
        $defaults = self::presetDefaults();
        $normalizedPreset = array_key_exists($preset, $defaults)
            ? $preset
            : self::defaultPreset();

        return $defaults[$normalizedPreset];
    }

    public function pdf(): BelongsTo
    {
        return $this->belongsTo(CatalogPdf::class, 'catalog_pdf_id');
    }

    public function applyPreset(string $preset, array $overrides = []): void
    {
        $normalizedPreset = array_key_exists($preset, self::presetOptions())
            ? $preset
            : self::defaultPreset();

        $this->preset = $normalizedPreset;
        $this->fill(array_merge(
            self::attributesForPreset($normalizedPreset),
            $overrides
        ));
    }

    public function viewerSettings(): array
    {
        $defaults = self::attributesForPreset($this->preset ?: self::defaultPreset());

        return [
            'duration' => (int) ($this->duration_ms ?? $defaults['duration_ms']),
            'gradients' => (bool) ($this->gradients ?? $defaults['gradients']),
            'acceleration' => (bool) ($this->acceleration ?? $defaults['acceleration']),
            'elevation' => (int) ($this->elevation ?? $defaults['elevation']),
            'displayMode' => (string) ($this->display_mode ?? $defaults['display_mode']),
            'renderScale' => (int) ($this->render_scale_percent ?? $defaults['render_scale_percent']) / 100,
        ];
    }
}
