<?php

declare(strict_types=1);

namespace App\Domain\Customers\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case BranchStaff = 'branch_staff';
    case BrandAdmin = 'brand_admin';
    case SuperAdmin = 'super_admin';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    public function isAdmin(): bool
    {
        return $this === self::BrandAdmin || $this === self::SuperAdmin;
    }

    public function isStaff(): bool
    {
        return $this === self::BranchStaff;
    }
}
