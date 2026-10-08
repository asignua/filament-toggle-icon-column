<?php

declare(strict_types=1);

namespace Asignua\FilamentToggleIconColumn\Tests\Fixtures;

use Closure;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Workbench\App\Models\Task;

/**
 * A bare table whose query and columns are set per test (independent review fixtures).
 */
class ReviewTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    /** @var (Closure(): array<int, mixed>)|null */
    public static ?Closure $columns = null;

    /** @var (Closure(): Builder<\Illuminate\Database\Eloquent\Model>)|null */
    public static ?Closure $query = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(static::$query !== null ? (static::$query)() : Task::query())
            ->columns(static::$columns !== null ? (static::$columns)() : []);
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>{{ $this->table }}</div>
            BLADE;
    }
}
