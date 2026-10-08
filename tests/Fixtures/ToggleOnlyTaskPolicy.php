<?php

declare(strict_types=1);

namespace Asignua\FilamentToggleIconColumn\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use Workbench\App\Models\Task;

/**
 * Denies `update` for every task, allows a custom `toggle` ability for unlocked ones.
 */
class ToggleOnlyTaskPolicy
{
    public function update(Authenticatable $user, Task $task): bool
    {
        return false;
    }

    public function toggle(Authenticatable $user, Task $task): bool
    {
        return !$task->is_locked;
    }
}
