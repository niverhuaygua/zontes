<?php

declare(strict_types=1);

namespace App\Domain\Customers\Enums;

enum MemberStatus: string
{
    case Active = 'active';
    case PendingReview = 'pending_review';
    case Suspended = 'suspended';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
