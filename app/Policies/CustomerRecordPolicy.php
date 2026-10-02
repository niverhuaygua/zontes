<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Customers\CustomerRecord;
use App\Models\User;

class CustomerRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, CustomerRecord $record): bool
    {
        return $user->isSuperAdmin() || $user->administersBrand($record->brand_id);
    }

    public function update(User $user, CustomerRecord $record): bool
    {
        return $this->view($user, $record);
    }
}
