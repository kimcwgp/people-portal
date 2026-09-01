<?php

namespace App\Policies;

use App\Models\Standup;
use App\Models\User;

class StandupPolicy
{
    /**
     * Perform pre-authorization checks.
     * Super Admins bypass all authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return null; // Continue with normal authorization
    }

    public function view(User $user, Standup $standup): bool
    {
        return $standup->user_id === $user->id;
    }

    public function update(User $user, Standup $standup): bool
    {
        return $standup->user_id === $user->id;
    }

    public function delete(User $user, Standup $standup): bool
    {
        return $standup->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return (bool) $user->id;
    }
}
