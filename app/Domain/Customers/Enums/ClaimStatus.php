<?php

declare(strict_types=1);

namespace App\Domain\Customers\Enums;

enum ClaimStatus: string
{
    /** Cliente precargado por CSV que todavia no tiene cuenta. */
    case Unclaimed = 'unclaimed';

    /** Enlazado automaticamente porque coinciden CI y el segundo dato. */
    case Claimed = 'claimed';

    /** Enlace pendiente de revision manual del administrador. */
    case PendingReview = 'pending_review';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
