<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Loyalty\PointRule;
use App\Models\User;

/**
 * Las reglas de puntos solo las gestionan administradores de la marca.
 */
class PointRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, PointRule $rule): bool
    {
        return $user->isSuperAdmin() || $user->administersBrand($rule->brand_id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, PointRule $rule): bool
    {
        return $user->isSuperAdmin() || $user->administersBrand($rule->brand_id);
    }

    public function delete(User $user, PointRule $rule): bool
    {
        return $this->update($user, $rule);
    }
}
