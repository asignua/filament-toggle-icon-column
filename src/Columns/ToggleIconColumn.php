<?php

declare(strict_types=1);

namespace Asignua\FilamentToggleIconColumn\Columns;

use BackedEnum;
use Closure;
use Filament\Facades\Filament;
use Filament\Forms\Components\Concerns\HasToggleColors;
use Filament\Forms\Components\Concerns\HasToggleIcons;
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
use Filament\Tables\View\Components\Columns\IconColumnComponent\IconComponent;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
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

    protected IconSize|Closure|null $size = null;

    protected bool|Closure $hasHoverHint = true;

    protected bool|string|Closure $authorization = true;

    protected bool|Closure $hasStateTooltip = false;

    protected function setUp(): void
    {
        parent::setUp();

        // The icon is the click target; a row-level URL/action must not fire with it.
        $this->disabledClick();

        $this->rules(['boolean']);
    }

    public function size(IconSize|Closure|null $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): IconSize
    {
        $size = $this->evaluate($this->size);

        return $size instanceof IconSize ? $size : IconSize::Large;
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

        return __('filament-toggle-icon-column::filament-toggle-icon-column.tooltip.'.($state ? 'on' : 'off'));
    }

    public function isDisabled(): bool
    {
        return parent::isDisabled() || !$this->isAuthorized();
    }

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

        if (Gate::getPolicyFor($record) === null) {
            return true;
        }

        $user = Filament::auth()->user();

        if (!($user instanceof Authenticatable)) {
            return false;
        }

        return Gate::forUser($user)->allows(is_string($ability) ? $ability : 'update', $record);
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
                    error: undefined,
                    isLoading: false,
                    isHovered: false,
                    unsubscribeLivewireHook: null,
                    get showOn() {
                        const preview = this.hint && this.isHovered && ! this.disabled && ! this.isLoading

                        return preview ? ! this.state : this.state
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

                            if (this.error) {
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

        ob_start(); ?>

        <div
            wire:ignore.self
            <?= $attributes->toHtml() ?>
        >
            <input type="hidden" value="<?= $state ? 1 : 0 ?>" x-ref="serverState" />

            <div <?= $buttonAttributes->toHtml() ?>>
                <span
                    aria-hidden="true"
                    x-show="showOn"
                    x-bind:style="! state && showOn ? 'opacity: .55' : null"
                    <?php if (!$state) { ?> x-cloak <?php } ?>
                ><?= $onIcon ?></span>

                <span
                    aria-hidden="true"
                    x-show="! showOn"
                    x-bind:style="state && ! showOn ? 'opacity: .55' : null"
                    <?php if ($state) { ?> x-cloak <?php } ?>
                ><?= $offIcon ?></span>
            </div>
        </div>

        <?php return (string) ob_get_clean();
    }
}
