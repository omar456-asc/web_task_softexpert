<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function view(User $user, Task $task)
    {
        return $user->hasRole('Manager') || $task->assignee_id === $user->id;
    }

    public function create(User $user)
    {
        return $user->hasRole('Manager');
    }

    public function update(User $user, Task $task)
    {
        return $user->hasRole('Manager');
    }

    public function updateStatus(User $user, Task $task)
    {
        return $user->hasRole('Manager') || ($user->hasRole('User') && $task->assignee_id === $user->id);
    }
}
