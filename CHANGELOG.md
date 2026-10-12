# Changelog

All notable changes to `asignua/filament-toggle-icon-column` are documented here.

## 1.0.1 - Unreleased

- Fix: `onColor()`, `offColor()` and `hoverColor()` had no visible effect (icons rendered grey). Core paints only `.fi-ta-icon > .fi-icon.fi-color`; the plugin now ships a small stylesheet that applies Filament's `--text` / `--dark-text` colour variables to its own markup, in light and dark mode. Run `php artisan filament:assets` after updating.
- Tooltips: clearer wording ("On. Click to turn off." / "Off. Click to turn on.") in every shipped language.
- Docs: screenshots, cover and a workbench demo.

## 1.0.0 (unreleased)

- `ToggleIconColumn`: a clickable icon that toggles a boolean in place (Filament 5 port of `archilex/filament-toggle-icon-column`).
- `onIcon`/`offIcon`/`onColor`/`offColor`, `size()`, `hoverHint()`, `stateTooltip()`, `tooltip()`, alignment.
- Saving through core `updateTableColumnState()`: `rules()`, `disabled()`, `beforeStateUpdated()`, `afterStateUpdated()`, `updateStateUsing()`.
- Model-policy gate (`authorize()`), on by default for models that have a policy.
- Accessible markup (`role="button"`, `aria-pressed`, keyboard support); works in tables and relation managers.
- Translations: en, uk, de, es, fr, it, nl, pl, pt_BR, tr.
