# Toggle Icon Column: spec

## Why this exists

`archilex/filament-toggle-icon-column` is a Filament 3 plugin (about 37k downloads a month) with no Filament 4/5 release.
Filament 5 core has no replacement: `ToggleColumn` is a switch (its `onIcon`/`offIcon` only decorate the knob), and `IconColumn`
is read-only (a column `action()` can flip a value, but there is no inline validation, no `beforeStateUpdated`/`afterStateUpdated`,
no optimistic UI, no `Editable` contract). Checked in `filament/tables` 5.x: only `ToggleColumn`, `CheckboxColumn`,
`TextInputColumn`, `SelectColumn` implement `Contracts\Editable`.

## Scope

One class, `Asignua\FilamentToggleIconColumn\Columns\ToggleIconColumn`: an icon you click to flip a boolean attribute in place.
It extends `Column`, implements `Editable` and `HasEmbeddedView`, and reuses `CanUpdateState` + `CanBeValidated`, so the stock
`updateTableColumnState()` Livewire method (in tables and in relation managers) saves, validates and returns the error.

## Public API

```php
ToggleIconColumn::make('is_active')
    ->onIcon(Heroicon::CheckCircle)->offIcon(Heroicon::XCircle)   // HasToggleIcons (defaults: outlined check / x circle)
    ->onColor('success')->offColor('gray')                        // HasToggleColors
    ->size(IconSize::Large)                                       // default Large
    ->hoverHint()                                                 // default on: preview the opposite icon, dimmed
    ->tooltip('...') / ->stateTooltip()                           // custom or built-in translated tooltip
    ->disabled(fn (Model $record): bool => ...)                   // stock
    ->authorize() / ->authorize('manage') / ->authorize(false)    // model policy gate, default 'update'
    ->rules(['boolean', ...])                                     // stock; default ['boolean']
    ->beforeStateUpdated() / ->afterStateUpdated() / ->updateStateUsing()   // stock (CanUpdateState)
    ->alignment(...) / ->alignCenter()                            // stock
```

## Behaviour and decisions

- Rendering: `div role="button"` with `aria-pressed`, `aria-label` (the column label), `aria-disabled`, `aria-busy`,
  `aria-invalid`. `tabindex` is bound client-side (the cell can sit inside the record's `<a>`), Enter and Space toggle.
  A `div` and not a `<button>` for the same reason as core's `ToggleColumn`.
- Client logic is an inline Alpine `x-data` object (no JS asset, nothing to publish or build), modelled on core's
  `toggleTableColumn`: optimistic flip, `updateTableColumnState`, revert to the server value on a validation error,
  resync after Livewire re-renders. Consequence: not compatible with the Alpine CSP build.
- Authorization: core editable columns only honour `disabled()`. This column additionally asks the model policy
  (`Gate::forUser(Filament::auth()->user())`, ability `update`) when the model HAS a policy; no policy means allowed (Filament's
  own convention). A denied record renders disabled and `updateTableColumnState()` refuses to write (the check lives in
  `isDisabled()`, which core calls server-side). Opt out with `->authorize(false)`.
- A row-level URL/action is disabled for this cell (`disabledClick()`), like `ToggleColumn`.
- Translations: en, uk (plus de, es, fr, it, nl, pl, pt_BR, tr) for the optional state tooltip.

## Extension points

Everything stock on `Column` (`state()`, `formatStateUsing` is irrelevant: the state is the raw boolean), the three
`CanUpdateState` callbacks, `extraAttributes()`. No plugin registration is needed: the column is a plain class; the panel plugin
(`ToggleIconColumnPlugin`) only exists for ecosystem consistency.

## Non-goals

- Not a general enum/state cycler (more than two states).
- No form-field or infolist counterpart (use `Toggle`).
- No bulk toggle action.
- No Alpine CSP build support.
- No per-cell loading spinner beyond `aria-busy` and the dimmed icon.
