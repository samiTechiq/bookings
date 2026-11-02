<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(UserRole::values());
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasAnyRole(UserRole::values());
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(UserRole::values());
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasAnyRole(UserRole::values());
    }

    /**
     * Only the Root Admin may delete records, to protect the audit trail.
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->isRootAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isRootAdmin();
    }
}
