<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Tasks;

use Asignua\FilamentToggleIconColumn\Columns\ToggleIconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Workbench\App\Filament\Resources\Tasks\Pages\ListTasks;
use Workbench\App\Models\Task;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
            Toggle::make('is_done'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title'),
            ToggleIconColumn::make('is_done')
                ->label('Done')
                ->onIcon('heroicon-s-check-circle')
                ->offIcon('heroicon-o-minus-circle')
                ->onColor('success')
                ->offColor('gray')
                ->tooltip('Toggle done'),
            ToggleIconColumn::make('is_locked')
                ->disabled(fn (Task $record): bool => $record->title === 'frozen')
                ->rules(['boolean', 'accepted']),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListTasks::route('/')];
    }
}
