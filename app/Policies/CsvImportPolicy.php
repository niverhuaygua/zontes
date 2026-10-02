<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Imports\CsvImport;
use App\Models\User;

class CsvImportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, CsvImport $import): bool
    {
        return $user->isSuperAdmin() || $user->administersBrand($import->brand_id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function process(User $user, CsvImport $import): bool
    {
        return $this->view($user, $import);
    }

    public function delete(User $user, CsvImport $import): bool
    {
        return $this->view($user, $import);
    }
}
