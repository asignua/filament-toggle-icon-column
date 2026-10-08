<?php

declare(strict_types=1);

namespace Asignua\FilamentToggleIconColumn\Tests\Feature;

use Asignua\FilamentToggleIconColumn\ToggleIconColumnPlugin;
use Asignua\FilamentToggleIconColumn\Tests\TestCase;
use Filament\Facades\Filament;

class SmokeTest extends TestCase
{
    public function test_the_panel_boots(): void
    {
        $this->assertSame('admin', Filament::getCurrentPanel()?->getId());
    }

    public function test_the_plugin_is_registered_on_the_panel(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue($panel->hasPlugin('asignua-filament-toggle-icon-column'));
        $this->assertInstanceOf(ToggleIconColumnPlugin::class, $panel->getPlugin('asignua-filament-toggle-icon-column'));
    }

    public function test_the_translations_are_loaded(): void
    {
        $this->assertSame('Sample', __('filament-toggle-icon-column::filament-toggle-icon-column.sample'));
    }
}
