<?php

namespace Tests\Unit;

use App\Models\CatalogPdfFlipPhysicsSetting;
use PHPUnit\Framework\TestCase;

class CatalogPdfFlipPhysicsSettingTest extends TestCase
{
    public function test_apply_preset_uses_expected_defaults(): void
    {
        $setting = new CatalogPdfFlipPhysicsSetting();

        $setting->applyPreset(CatalogPdfFlipPhysicsSetting::PRESET_SMOOTH);

        $this->assertSame(CatalogPdfFlipPhysicsSetting::PRESET_SMOOTH, $setting->preset);
        $this->assertSame(1200, $setting->duration_ms);
        $this->assertTrue($setting->gradients);
        $this->assertTrue($setting->acceleration);
        $this->assertSame(70, $setting->elevation);
        $this->assertSame('auto', $setting->display_mode);
        $this->assertSame(140, $setting->render_scale_percent);
    }

    public function test_apply_preset_accepts_custom_overrides(): void
    {
        $setting = new CatalogPdfFlipPhysicsSetting();

        $setting->applyPreset(CatalogPdfFlipPhysicsSetting::PRESET_SNAPPY, [
            'duration_ms' => 800,
            'gradients' => false,
            'acceleration' => false,
            'elevation' => 44,
            'display_mode' => 'double',
            'render_scale_percent' => 135,
        ]);

        $this->assertSame(CatalogPdfFlipPhysicsSetting::PRESET_SNAPPY, $setting->preset);
        $this->assertSame(800, $setting->duration_ms);
        $this->assertFalse($setting->gradients);
        $this->assertFalse($setting->acceleration);
        $this->assertSame(44, $setting->elevation);
        $this->assertSame('double', $setting->display_mode);
        $this->assertSame(135, $setting->render_scale_percent);
    }
}
