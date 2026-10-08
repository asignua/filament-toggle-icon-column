<?php

declare(strict_types=1);

namespace Asignua\FilamentToggleIconColumn\Columns;

use BackedEnum;
use Closure;
use Filament\Facades\Filament;
use Filament\Forms\Components\Concerns\HasToggleColors;
use Filament\Forms\Components\Concerns\HasToggleIcons;

use function Filament\get_authorization_response;

use Filament\Support\Components\Contracts\HasEmbeddedView;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\IconSize;

use function Filament\Support\generate_icon_html;

use Filament\Support\Icons\Heroicon;
use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Concerns\CanBeValidated;
use Filament\Tables\Columns\Concerns\CanUpdateState;
use Filament\Tables\Columns\Contracts\Editable;
use Filament\Tables\Table;
use Filament\Tables\View\Components\Columns\IconColumnComponent\IconComponent;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Js;

/**
 * A boolean column that is an icon you click: it flips the attribute in place, the way
 * `ToggleColumn` does, but without the switch chrome.
 *
 * Saving goes through the stock `updateTableColumnState()` Livewire method, so `disabled()`,
 * `rules()`, `beforeStateUpdated()`, `afterStateUpdated()` and `updateStateUsing()` behave
 * exactly as on the core editable columns. On top of that the column asks the model policy
 * (`update`) before it writes, see {@see self::authorize()}.
 */
class ToggleIconColumn extends Column implements Editable, HasEmbeddedView
{
    use CanBeValidated;
    use CanUpdateState;
    use HasToggleColors;
    use HasToggleIcons;

    protected IconSize|string|Closure|null $size = null;

    /** @var array<string>|Closure|string|null */
    protected string|array|Closure|null $hoverColor = null;

    protected bool|Closure $hasHoverHint = true;

    protected bool|string|Closure $authorization = true;

    protected bool|Closure $hasStateTooltip = false;

    protected function setUp(): void
    {
        parent::setUp();

        // The icon is the click target; a row-level URL/action must not fire with it.
        $this->disabledClick();

        // `required` rejects crafted null/'' input, which would otherwise be written as NULL.
        $this->rules(['required', 'boolean']);
    }

    /**
     * An {@see IconSize} or, as in archilex's column and core `IconColumn`, its string value
     * (`xs`, `sm`, `md`, `lg`, `xl`, `2xl`).
     */
    public function size(IconSize|string|Closure|null $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): IconSize
    {
        $size = $this->evaluate($this->size);

        if (is_string($size)) {
            $size = IconSize::tryFrom($size);
        }

        return $size instanceof IconSize ? $size : IconSize::Large;
    }

    /**
     * The colour of the previewed (hover/focus) icon. Defaults to the colour of the state the
     * click would produce.
     *
     * @param array<string>|Closure|string|null $color
     */
    public function hoverColor(string|array|Closure|null $color): static
    {
        $this->hoverColor = $color;

        return $this;
    }

    /**
     * @return array<string>|string|null
     */
    public function getHoverColor(): string|array|null
    {
        return $this->evaluate($this->hoverColor);
    }

    /**
     * While the pointer (or keyboard focus) is on an enabled icon, preview the state the click
     * would produce: the opposite icon, dimmed.
     */
    public function hoverHint(bool|Closure $condition = true): static
    {
        $this->hasHoverHint = $condition;

        return $this;
    }

    public function hasHoverHint(): bool
    {
        return (bool) $this->evaluate($this->hasHoverHint);
    }

    /**
     * Gate the write on a model policy. Unlike the core editable columns (which only honour
     * `disabled()`), the column is read-only for a user whom the policy denies.
     *
     * `true` (default) asks the `update` ability, a string names another ability, `false` turns
     * the check off. A closure receives the record and returns the same. A model without a
     * policy is allowed, as in Filament resources.
     */
    public function authorize(bool|string|Closure $ability = 'update'): static
    {
        $this->authorization = $ability;

        return $this;
    }

    /**
     * Fall back to a built-in tooltip («Enabled, click to disable») when no `tooltip()` is set.
     */
    public function stateTooltip(bool|Closure $condition = true): static
    {
        $this->hasStateTooltip = $condition;

        return $this;
    }

    public function getTooltip(mixed $state = null, ?Model $relatedRecord = null): string|Htmlable|null
    {
        $tooltip = parent::getTooltip($state, $relatedRecord);

        if (filled($tooltip) || !$this->evaluate($this->hasStateTooltip)) {
            return $tooltip;
        }

        // A cell that cannot be clicked must not say "click to ..."; it only reports the state.
        return __('filament-toggle-icon-column::filament-toggle-icon-column.tooltip.'.($state ? 'on' : 'off').($this->isDisabled() ? '_readonly' : ''));
    }

    public function isDisabled(): bool
    {
        return parent::isDisabled() || !$this->isAuthorized();
    }

    /**
     * Asks the policy of EVERY record the write touches: the row, and for a relationship column
     * (`user.is_active`) the related record, for a pivot column the pivot. Same semantics as
     * Filament resources ({@see get_authorization_response()}), so the panel's
     * `strictAuthorization()` is honoured and a policy without the method allows.
     */
    public function isAuthorized(): bool
    {
        $ability = $this->evaluate($this->authorization);

        if ($ability === false) {
            return true;
        }

        $record = $this->getRecord();

        if (!($record instanceof Model)) {
            return true;
        }

        if (!(Filament::auth()->user() instanceof Authenticatable)) {
            return false;
        }

        $ability = is_string($ability) ? $ability : 'update';

        foreach ($this->getWrittenRecords($record) as $target) {
            if (!get_authorization_response($ability, $target)->allowed()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, Model>
     */
    protected function getWrittenRecords(Model $record): array
    {
        $records = [$record];

        if ($this->hasRelationship($record)) {
            $relationshipName = (string) $this->getRelationshipName($record);
            $related = Arr::get($record->loadMissing($relationshipName), $relationshipName);

            if ($related instanceof Model) {
                $records[] = $related;
            }

            return $records;
        }

        $tableRelationship = $this->getTable()->getRelationship();

        if (
            ($tableRelationship instanceof BelongsToMany)
            && in_array($this->getAttributeName($record), $tableRelationship->getPivotColumns(), true)
        ) {
            $pivot = $record->getRelationValue($tableRelationship->getPivotAccessor());

            // A bare pivot has no policy; asking about it would only trip strict mode.
            if (($pivot instanceof Model) && filled(Gate::getPolicyFor($pivot))) {
                $records[] = $pivot;
            }
        }

        return $records;
    }

    public function getOnColor(): ?string
    {
        return $this->evaluate($this->onColor) ?? 'success';
    }

    public function getOffColor(): ?string
    {
        return $this->evaluate($this->offColor) ?? 'gray';
    }

    public function getOnIcon(): string|BackedEnum|Htmlable|null
    {
        return $this->evaluate($this->onIcon) ?? Heroicon::OutlinedCheckCircle;
    }

    public function getOffIcon(): string|BackedEnum|Htmlable|null
    {
        return $this->evaluate($this->offIcon) ?? Heroicon::OutlinedXCircle;
    }

    public function toEmbeddedHtml(): string
    {
        $state = (bool) $this->getState();
        $relatedRecord = $this->getRelatedRecord();
        $isDisabled = $this->isDisabled();
        $alignment = $this->getAlignment();
        $size = $this->getSize();
        $label = e(trim(strip_tags(($label = $this->getLabel()) instanceof Htmlable ? $label->toHtml() : (string) $label)), doubleEncode: false);

        $attributes = $this->getExtraAttributeBag()
            ->merge([
                'x-data' => '{
                    name: '.Js::from($this->getName()).',
                    recordKey: '.Js::from($this->getRecordKey()).',
                    state: '.Js::from($state).',
                    disabled: '.Js::from($isDisabled).',
                    hint: '.Js::from($this->hasHoverHint()).',
                    custom: '.Js::from($this->updateStateUsing !== null).',
                    error: undefined,
                    isLoading: false,
                    isHovered: false,
                    unsubscribeLivewireHook: null,
                    get previewing() {
                        return this.hint && this.isHovered && ! this.disabled && ! this.isLoading
                    },
                    get showOn() {
                        return this.previewing ? ! this.state : this.state
                    },
                    getServerState() {
                        if (! this.$refs.serverState) {
                            return undefined
                        }

                        return [1, \'1\'].includes(this.$refs.serverState.value)
                    },
                    toggle() {
                        if (this.disabled || this.isLoading) {
                            return
                        }

                        this.state = ! this.state
                    },
                    init() {
                        this.unsubscribeLivewireHook = Livewire.interceptMessage(({ message, onSuccess }) => {
                            onSuccess(() => {
                                this.$nextTick(() => {
                                    if (this.isLoading) {
                                        return
                                    }

                                    if (message.component.id !== this.$root.closest(\'[wire\\\\:id]\')?.attributes[\'wire:id\'].value) {
                                        return
                                    }

                                    const serverState = this.getServerState()

                                    if (serverState === undefined || Alpine.raw(this.state) === serverState) {
                                        return
                                    }

                                    this.state = serverState
                                })
                            })
                        })

                        this.$watch(\'state\', async () => {
                            const serverState = this.getServerState()

                            if (serverState === undefined || Alpine.raw(this.state) === serverState) {
                                return
                            }

                            this.isLoading = true

                            const response = await this.$wire.updateTableColumnState(this.name, this.recordKey, this.state)

                            this.error = response?.error ?? undefined

                            // The server answers null when it refused the write (disabled, denied, hidden,
                            // record gone); only `updateStateUsing()` may legitimately return nothing.
                            const refused = ! this.custom && (response === null || response === undefined)

                            if (this.error || refused) {
                                this.state = serverState
                            } else if (this.$refs.serverState) {
                                this.$refs.serverState.value = this.state ? \'1\' : \'0\'
                            }

                            this.isLoading = false
                        })
                    },
                    destroy() {
                        this.unsubscribeLivewireHook?.()
                    },
                }',
            ], escape: false)
            ->class([
                'fi-ta-toggle-icon',
                (($alignment instanceof Alignment) ? "fi-align-{$alignment->value}" : (is_string($alignment) ? $alignment : '')),
                'fi-inline' => $this->isInline(),
            ]);

        $buttonAttributes = (new FilamentComponentAttributeBag)
            ->merge([
                'role' => 'button',
                'aria-pressed' => $state ? 'true' : 'false',
                'x-bind:aria-pressed' => 'state ? \'true\' : \'false\'',
                'aria-disabled' => $isDisabled ? 'true' : null,
                'aria-label' => $label,
                // Inert while the table re-queries (sort, page, filter, search), as core ToggleColumn is.
                'wire:loading.attr' => 'inert',
                'wire:target' => implode(',', Table::LOADING_TARGETS),
                'x-bind:aria-busy' => 'isLoading ? \'true\' : null',
                'x-bind:aria-invalid' => 'error !== undefined ? \'true\' : null',
                // `tabindex` is applied client-side on purpose: the cell may sit inside the record's
                // `<a>`/`<button>`, where a server-rendered `tabindex` descendant would be invalid markup.
                'x-bind:tabindex' => 'disabled ? \'-1\' : \'0\'',
                'x-on:click.prevent.stop' => 'toggle()',
                'x-on:keydown.enter.prevent.stop' => 'toggle()',
                'x-on:keydown.space.prevent.stop' => 'toggle()',
                'x-on:mouseenter' => 'isHovered = true',
                'x-on:mouseleave' => 'isHovered = false',
                'x-on:focus' => 'isHovered = true',
                'x-on:blur' => 'isHovered = false',
                'x-bind:class' => '{ \'fi-toggle-icon-loading\': isLoading, \'fi-toggle-icon-disabled\': disabled }',
                'x-tooltip' => 'error === undefined ? '.(filled($tooltip = $this->getTooltip($state, $relatedRecord))
                    ? '{
                        content: '.Js::from($tooltip instanceof Htmlable ? $tooltip->toHtml() : $tooltip).',
                        theme: $store.theme,
                        allowHTML: '.Js::from($tooltip instanceof Htmlable).',
                    }'
                    : 'false').' : {
                        content: error,
                        theme: $store.theme,
                        allowHTML: false,
                    }',
            ], escape: false)
            ->class(['fi-toggle-icon', 'fi-toggle-icon-disabled' => $isDisabled])
            ->style(['display: inline-flex', 'cursor: '.($isDisabled ? 'default' : 'pointer')]);

        $onIcon = generate_icon_html(
            $this->getOnIcon(),
            attributes: (new FilamentComponentAttributeBag)->color(IconComponent::class, $this->getOnColor() ?? 'success'),
            size: $size,
        )?->toHtml();

        $offIcon = generate_icon_html(
            $this->getOffIcon(),
            attributes: (new FilamentComponentAttributeBag)->color(IconComponent::class, $this->getOffColor() ?? 'gray'),
            size: $size,
        )?->toHtml();

        $hoverColor = $this->getHoverColor();

        $previewIcon = static fn (mixed $icon, mixed $color): string => generate_icon_html(
            $icon,
            attributes: (new FilamentComponentAttributeBag)->color(IconComponent::class, $color),
            size: $size,
        )?->toHtml() ?? '';

        // Without hoverColor() the previewed icon is just the opposite state's icon, so two spans do;
        // with it, each state gets a second span that is shown only while previewed.
        $variants = $hoverColor === null
            ? [
                ['show' => 'showOn', 'cloak' => !$state, 'html' => $onIcon],
                ['show' => '! showOn', 'cloak' => $state, 'html' => $offIcon],
            ]
            : [
                ['show' => 'showOn && ! previewing', 'cloak' => !$state, 'html' => $onIcon],
                ['show' => 'showOn && previewing', 'cloak' => true, 'html' => $previewIcon($this->getOnIcon(), $hoverColor)],
                ['show' => '! showOn && ! previewing', 'cloak' => $state, 'html' => $offIcon],
                ['show' => '! showOn && previewing', 'cloak' => true, 'html' => $previewIcon($this->getOffIcon(), $hoverColor)],
            ];

        ob_start(); ?>

        <div
            wire:ignore.self
            <?= $attributes->toHtml() ?>
        >
            <input type="hidden" value="<?= $state ? 1 : 0 ?>" x-ref="serverState" />

            <div <?= $buttonAttributes->toHtml() ?>>
                <?php foreach ($variants as $variant) { ?>
                    <span
                        aria-hidden="true"
                        x-show="<?= $variant['show'] ?>"
                        x-bind:style="{ opacity: previewing ? '.55' : '' }"
                        <?php if ($variant['cloak']) { ?> x-cloak <?php } ?>
                    ><?= $variant['html'] ?></span>
                <?php } ?>
            </div>
        </div>

        <?php return (string) ob_get_clean();
    }
}
