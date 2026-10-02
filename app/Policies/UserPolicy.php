<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Gestion de usuarios y roles (panel admin).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        // Un administrador de marca no puede tocar a super_admin ni a otros admin.
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isAdmin() && ! $model->role->isAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $user->getKey() !== $model->getKey();
    }
}
