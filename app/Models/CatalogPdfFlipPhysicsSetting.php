<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogPdfFlipPhysicsSetting extends Model
{
    use HasFactory;

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

    public static function presetOptions(): array
    {
        return [
            self::PRESET_REALISTIC => 'Realistic (default)',
            self::PRESET_SNAPPY => 'Snappy (fast)',
            self::PRESET_SMOOTH => 'Smooth (soft)',
            self::PRESET_MINIMAL => 'Minimal (lightweight)',
        ];
    }

    public function pdf(): BelongsTo
    {
        return $this->belongsTo(CatalogPdf::class, 'catalog_pdf_id');
    }

    public function applyPreset(string $preset): void
    {
        $this->preset = $preset;

        switch ($preset) {
            case self::PRESET_SNAPPY:
                $this->duration_ms = 550;
                $this->gradients = true;
                $this->acceleration = true;
                $this->elevation = 30;
                $this->render_scale_percent = 110;
                break;

            case self::PRESET_SMOOTH:
                $this->duration_ms = 1200;
                $this->gradients = true;
                $this->acceleration = true;
                $this->elevation = 70;
                $this->render_scale_percent = 140;
                break;

            case self::PRESET_MINIMAL:
                $this->duration_ms = 700;
                $this->gradients = false;
                $this->acceleration = false;
                $this->elevation = 10;
                $this->render_scale_percent = 100;
                break;

            case self::PRESET_REALISTIC:
            default:
                $this->duration_ms = 900;
                $this->gradients = true;
                $this->acceleration = true;
                $this->elevation = 50;
                $this->render_scale_percent = 120;
                break;
        }
    }
}
