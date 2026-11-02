<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Supply;
use App\Models\User;

class SupplyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(UserRole::values());
    }

    public function view(User $user, Supply $supply): bool
    {
        return $user->hasAnyRole(UserRole::values());
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(UserRole::values());
    }

    public function update(User $user, Supply $supply): bool
    {
        return $user->hasAnyRole(UserRole::values());
    }

    /**
     * Only the Root Admin may delete records, to protect the audit trail.
     */
    public function delete(User $user, Supply $supply): bool
    {
        return $user->isRootAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isRootAdmin();
    }
}
