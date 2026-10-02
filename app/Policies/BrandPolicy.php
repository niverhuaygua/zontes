<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;

/**
 * Solo el super_admin gestiona marcas. Los brand_admin pueden ver las suyas.
 */
class BrandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isBranchStaff();
    }

    public function view(User $user, Brand $brand): bool
    {
        return $user->isSuperAdmin() || $user->administersBrand($brand);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->isSuperAdmin() || $user->administersBrand($brand);
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $user->isSuperAdmin();
    }
}
