<?php

declare(strict_types=1);

namespace Asignua\FilamentToggleIconColumn\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use Workbench\App\Models\User;

class DenyUpdateUserPolicy
{
    public function update(Authenticatable $user, User $model): bool
    {
        return false;
    }
}
