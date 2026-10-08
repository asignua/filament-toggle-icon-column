# Changelog

All notable changes to `asignua/filament-toggle-icon-column` are documented here.

## 1.0.0 (unreleased)

- `ToggleIconColumn`: a clickable icon that toggles a boolean in place (Filament 5 port of `archilex/filament-toggle-icon-column`).
- `onIcon`/`offIcon`/`onColor`/`offColor`, `size()`, `hoverHint()`, `stateTooltip()`, `tooltip()`, alignment.
- Saving through core `updateTableColumnState()`: `rules()`, `disabled()`, `beforeStateUpdated()`, `afterStateUpdated()`, `updateStateUsing()`.
- Model-policy gate (`authorize()`), on by default for models that have a policy.
- Accessible markup (`role="button"`, `aria-pressed`, keyboard support); works in tables and relation managers.
- Translations: en, uk, de, es, fr, it, nl, pl, pt_BR, tr.
