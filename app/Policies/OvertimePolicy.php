<?php

namespace App\Policies;

use App\Models\Overtime;
use App\Models\User;

class OvertimePolicy
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

    public function view(User $user, Overtime $overtime): bool
    {
        return $overtime->user_id === $user->id;
    }

    public function update(User $user, Overtime $overtime): bool
    {
        return $overtime->user_id === $user->id
            && $overtime->status === 'PENDING';
    }

    public function delete(User $user, Overtime $overtime): bool
    {
        return $overtime->user_id === $user->id
            && $overtime->status === 'PENDING';
    }

    public function create(User $user): bool
    {
        return (bool) $user->id;
    }

    public function approve(User $user, Overtime $overtime): bool
    {
        return $overtime->user->immediate_sup_id === $user->id
            && $overtime->status === 'PENDING';
    }

    public function reject(User $user, Overtime $overtime): bool
    {
        return $overtime->user->immediate_sup_id === $user->id
            && $overtime->status === 'PENDING';
    }
}
