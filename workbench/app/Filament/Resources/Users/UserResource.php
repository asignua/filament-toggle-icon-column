<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Users;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Workbench\App\Filament\Resources\Users\Pages\EditUser;
use Workbench\App\Filament\Resources\Users\RelationManagers\TasksRelationManager;
use Workbench\App\Models\User;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function getRelations(): array
    {
        return [TasksRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['edit' => EditUser::route('/{record}/edit')];
    }
}
