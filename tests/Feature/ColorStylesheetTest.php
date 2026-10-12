<?php

declare(strict_types=1);

namespace Asignua\FilamentToggleIconColumn\Tests\Feature;

use Asignua\FilamentToggleIconColumn\Tests\TestCase;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Livewire;
use Workbench\App\Filament\Resources\Tasks\Pages\ListTasks;
use Workbench\App\Models\Task;

/**
 * The colours of the icons reach the screen only through CSS: core paints `.fi-ta-icon > .fi-icon.fi-color`,
 * which never matches this column's markup. The plugin ships its own rule for it.
 */
class ColorStylesheetTest extends TestCase
{
    public function test_the_stylesheet_is_registered_as_a_panel_asset(): void
    {
        $css = collect(FilamentAsset::getStyles(['asignua/filament-toggle-icon-column']))
            ->first(fn ($asset): bool => $asset instanceof Css && $asset->getId() === 'filament-toggle-icon-column');

        $this->assertInstanceOf(Css::class, $css);
        $this->assertFileExists($css->getPath());
    }

    public function test_the_stylesheet_paints_coloured_icons_in_light_and_dark_mode(): void
    {
        $css = (string) file_get_contents(__DIR__.'/../../resources/dist/filament-toggle-icon-column.css');

        $this->assertStringContainsString('.fi-ta-toggle-icon .fi-icon.fi-color {'."\n".'    color: var(--text);', $css);
        $this->assertStringContainsString('.dark .fi-ta-toggle-icon .fi-icon.fi-color {'."\n".'    color: var(--dark-text);', $css);
    }

    public function test_the_icons_carry_the_classes_the_stylesheet_hooks_into(): void
    {
        Task::query()->create(['title' => 'Write docs']);

        $html = Livewire::test(ListTasks::class)->html();

        $this->assertStringContainsString('fi-ta-toggle-icon', $html);
        $this->assertMatchesRegularExpression('/fi-icon[^"]*fi-color|fi-color[^"]*fi-icon/', $html);
    }
}
