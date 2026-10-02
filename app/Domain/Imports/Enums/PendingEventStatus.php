<?php

declare(strict_types=1);

namespace App\Domain\Imports\Enums;

enum PendingEventStatus: string
{
    case Pending = 'pending';
    case Credited = 'credited';
    case Rejected = 'rejected';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
