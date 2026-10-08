<?php

declare(strict_types=1);

namespace Workbench\App\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Workbench\App\Models\Task;

/**
 * Registered only by the tests that exercise authorization.
 */
class TaskPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function view(Authenticatable $user, Task $task): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    public function update(Authenticatable $user, Task $task): bool
    {
        return !$task->is_locked;
    }

    public function delete(Authenticatable $user, Task $task): bool
    {
        return true;
    }
}
