## Filament Toggle Icon Column (asignua/filament-toggle-icon-column)

- A table column, no panel registration required: `Asignua\FilamentToggleIconColumn\Columns\ToggleIconColumn::make('is_active')`. It saves through Filament's `updateTableColumnState()`, so it works in resource tables and relation managers.
- Same API as `ToggleColumn` for saving: `rules([...])`, `disabled(Closure)`, `beforeStateUpdated()`, `afterStateUpdated()`, `updateStateUsing()`. Icons and colors: `onIcon()`, `offIcon()`, `onColor()`, `offColor()`; plus `size(IconSize)`, `hoverHint(bool)`, `stateTooltip()`.
- It asks the model policy's `update` ability before writing (only when the model has a policy). Opt out with `->authorize(false)`; name another ability with `->authorize('manage')`.
- Always pass `rules()` as an array, never a pipe string. Not compatible with the Alpine CSP build (inline `x-data`).
