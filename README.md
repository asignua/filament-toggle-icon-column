# Filament Toggle Icon Column

[![Tests](https://img.shields.io/github/actions/workflow/status/asignua/filament-toggle-icon-column/tests.yml?branch=main&label=tests)](https://github.com/asignua/filament-toggle-icon-column/actions/workflows/tests.yml)

A table column that is an icon you click: it flips a boolean attribute in place, with no switch and no page reload. The Filament 5
successor of `archilex/filament-toggle-icon-column` (which stopped at Filament 3).

Filament core has `ToggleColumn` (a switch) and `IconColumn` (read-only), but nothing that is an *editable icon*. This column adds
the missing piece and keeps the core saving contract: validation, `beforeStateUpdated`/`afterStateUpdated`, `disabled()`,
and, unlike the core editable columns, a model-policy check.

## Screenshots

TODO: add images to `art/` (cover.jpg first) and reference them here.


## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Filament 5

## Installation

```bash
composer require asignua/filament-toggle-icon-column
```

The column is a plain class and needs no registration. Registering `ToggleIconColumnPlugin::make()` on a panel is optional and only
marks the plugin as installed.

## Usage

```php
use Asignua\FilamentToggleIconColumn\Columns\ToggleIconColumn;
use Filament\Support\Enums\IconSize;

ToggleIconColumn::make('is_active')
    ->onIcon('heroicon-s-check-circle')
    ->offIcon('heroicon-o-x-circle')
    ->onColor('success')
    ->offColor('gray')
    ->size(IconSize::Large)
    ->tooltip('Toggle active'),
```

It works in resource tables and in relation managers (both use Filament's `updateTableColumnState()`).

### Saving, validation and hooks

Same API as `ToggleColumn`:

```php
ToggleIconColumn::make('is_published')
    ->rules(['boolean'])                                  // default; an error shows in the tooltip and the icon reverts
    ->beforeStateUpdated(fn (Post $record, bool $state) => ...)
    ->afterStateUpdated(fn (Post $record, bool $state) => ...)
    ->updateStateUsing(fn (Post $record, bool $state) => $record->publish($state));  // replaces the default write
```

### Disabling and authorization

```php
->disabled(fn (Post $record): bool => $record->is_archived)   // greyed out, and the server refuses to write
->authorize()            // default: Gate `update` on the record, only when the model has a policy
->authorize('publish')   // another ability
->authorize(false)       // skip the policy check (core ToggleColumn behaviour)
```

Core's editable columns only honour `disabled()`; this column also asks the policy, so a user who may not edit the record cannot
flip it by crafting a Livewire call. A model without a policy stays editable, as in Filament resources.

### Hover hint

By default, hovering (or focusing) an enabled icon previews the state the click would produce: the opposite icon, dimmed.
Turn it off with `->hoverHint(false)`. `->stateTooltip()` adds a translated tooltip ("Enabled. Click to disable.") when you set none.

### Accessibility

The icon is a `role="button"` with `aria-pressed`, `aria-label` (the column label), `aria-disabled`, `aria-busy` and `aria-invalid`;
Enter and Space toggle it, and disabled icons leave the tab order.

## Configuration

There is no config file; everything is a fluent setter on the column (see Usage). `size()`, `hoverHint()`, `authorize()` and
`stateTooltip()` are the additions over the core editable column API.

## Gotchas

- **Rules as an array.** `rules(['boolean', 'accepted'])`, never a pipe string.
- **Alpine CSP build** is not supported: the column's client logic is an inline `x-data` object.
- **Row URL / record action.** Clicks on the icon never reach the row action (`disabledClick()` is on); the rest of the cell does.
- **`updateStateUsing()` replaces the write**, including for JSON paths such as `settings.flag`; the default write handles those itself.
- **Policy without `update`.** If your policy class lacks the ability, Laravel denies it; define it or use `->authorize(false)`.

## Translations

The interface ships in English, Ukrainian, German, Spanish, French, Italian, Dutch, Polish, Brazilian Portuguese and Turkish
under the `filament-toggle-icon-column::filament-toggle-icon-column` namespace. A test keeps every language in step with the English keys. Override a
string by publishing the translations (`--tag=filament-toggle-icon-column-translations`) and editing the copy in
`lang/vendor/filament-toggle-icon-column`.

## AI agents

The package ships [Laravel Boost](https://laravel.com/docs/boost) guidelines (`resources/boost/guidelines/core.blade.php`) so a
coding agent wires it up correctly.

## Testing

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/pint --test
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
