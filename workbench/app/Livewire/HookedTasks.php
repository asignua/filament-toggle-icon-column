<?php

declare(strict_types=1);

namespace Workbench\App\Livewire;

use Asignua\FilamentToggleIconColumn\Columns\ToggleIconColumn;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Workbench\App\Models\Task;

/**
 * A bare table with the column's hooks wired to a static log, so tests can observe the order.
 */
class HookedTasks extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    /** @var list<string> */
    public static array $log = [];

    public function table(Table $table): Table
    {
        return $table
            ->query(Task::query())
            ->columns([
                TextColumn::make('title'),
                ToggleIconColumn::make('is_done')
                    ->beforeStateUpdated(function (Task $record, $state): void {
                        self::$log[] = 'before:'.var_export($record->is_done, true).'->'.var_export($state, true);
                    })
                    ->afterStateUpdated(function (Task $record, $state): void {
                        self::$log[] = 'after:'.var_export($record->fresh()?->is_done, true);
                    }),
                ToggleIconColumn::make('is_locked')
                    ->updateStateUsing(function (Task $record, $state): bool {
                        self::$log[] = 'custom:'.var_export($state, true);

                        return (bool) $state;
                    }),
                ToggleIconColumn::make('settings.flag')
                    ->hoverHint(false)
                    ->stateTooltip()
                    ->size(IconSize::Small)
                    ->alignCenter(),
            ]);
    }

    public function render(): View
    {
        return view('livewire.hooked-tasks');
    }
}
