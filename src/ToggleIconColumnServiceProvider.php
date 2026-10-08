<?php

declare(strict_types=1);

namespace Asignua\FilamentToggleIconColumn;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ToggleIconColumnServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-toggle-icon-column';

    public function configurePackage(Package $package): void
    {
        // Translations live in resources/lang/<locale>/filament-toggle-icon-column.php and are read as
        // `__('filament-toggle-icon-column::filament-toggle-icon-column.<key>')`. Publish tag: `filament-toggle-icon-column-translations`.
        $package->name(static::$name)
            ->hasTranslations()
            ->hasViews();

        // Add a config file only when the plugin really has options: create config/filament-toggle-icon-column.php and
        // chain `->hasConfigFile()` here (publish tag `filament-toggle-icon-column-config`). Prefer fluent setters on the Plugin.
    }
}
