<?php

namespace App\Policies;

use App\Models\User;

/**
 * User management is reserved for the Root Admin.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isRootAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isRootAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isRootAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isRootAdmin();
    }

    /**
     * The Root Admin cannot delete their own account.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isRootAdmin() && $user->isNot($model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isRootAdmin();
    }
}
