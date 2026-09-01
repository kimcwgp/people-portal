<?php

namespace App\Policies;

use App\Models\ShiftChangeRequest;
use App\Models\User;

class ShiftChangeRequestPolicy
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

    public function view(User $user, ShiftChangeRequest $request): bool
    {
        return $request->user_id === $user->id;
    }

    public function update(User $user, ShiftChangeRequest $request): bool
    {
        return $request->user_id === $user->id && $request->status === 'pending';
    }

    public function delete(User $user, ShiftChangeRequest $request): bool
    {
        return $request->user_id === $user->id && $request->status === 'pending';
    }

    public function create(User $user): bool
    {
        return (bool) $user->id;
    }

    public function approve(User $user, ShiftChangeRequest $request): bool
    {
        return $request->user->immediate_sup_id === $user->id
            && $request->status === 'pending';
    }

    public function reject(User $user, ShiftChangeRequest $request): bool
    {
        return $request->user->immediate_sup_id === $user->id
            && $request->status === 'pending';
    }
}
