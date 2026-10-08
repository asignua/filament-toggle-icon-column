<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Users\RelationManagers;

use Asignua\FilamentToggleIconColumn\Columns\ToggleIconColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title'),
            ToggleIconColumn::make('is_done'),
        ]);
    }
}
