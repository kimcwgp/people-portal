<?php

namespace App\Policies;

use App\Models\User;
use App\Models\HrAnnouncement;
use Illuminate\Auth\Access\HandlesAuthorization;

class HrAnnouncementPolicy
{
    use HandlesAuthorization;

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

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, HrAnnouncement $announcement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->canManageAnnouncements($user);
    }

    public function update(User $user, HrAnnouncement $announcement): bool
    {
        return $this->canManageAnnouncements($user);
    }

    public function delete(User $user, HrAnnouncement $announcement): bool
    {
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole(['admin', 'super_admin']);
        }

        if (isset($user->role)) {
            return in_array($user->role, ['admin', 'super_admin']);
        }

        return false;
    }

    private function canManageAnnouncements(User $user): bool
    {
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole(['admin', 'hr', 'super_admin']);
        }

        if (isset($user->role)) {
            return in_array($user->role, ['admin', 'hr', 'super_admin']);
        }

        if (method_exists($user, 'hasPermission')) {
            return $user->hasPermission('manage_hr_announcements');
        }

        return false;
    }
}