<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;

class LeavePolicy
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

    public function view(User $user, Leave $leave): bool
    {
        return $leave->user_id === $user->id;
    }

    public function update(User $user, Leave $leave): bool
    {
        return $leave->user_id === $user->id && $leave->status === 'pending';
    }

    public function delete(User $user, Leave $leave): bool
    {
        return $leave->user_id === $user->id && $leave->status === 'pending';
    }

    public function create(User $user): bool
    {
        return (bool) $user->id;
    }

    public function approve(User $user, Leave $leave): bool
    {
        return $leave->user->immediate_sup_id === $user->id
            && $leave->status === 'pending';
    }

    public function reject(User $user, Leave $leave): bool
    {
        return $leave->user->immediate_sup_id === $user->id
            && $leave->status === 'pending';
    }
}
