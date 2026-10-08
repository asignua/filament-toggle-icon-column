<?php

declare(strict_types=1);

namespace Asignua\FilamentToggleIconColumn\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use Workbench\App\Models\Task;

/**
 * A policy without an `update` method (Filament resources treat a missing method as "allowed").
 */
class ViewOnlyTaskPolicy
{
    public function view(Authenticatable $user, Task $task): bool
    {
        return true;
    }
}
