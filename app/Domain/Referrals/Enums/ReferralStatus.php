<?php

declare(strict_types=1);

namespace App\Domain\Referrals\Enums;

enum ReferralStatus: string
{
    /** El referido se registro: falta que realize su primera compra. */
    case Registered = 'registered';

    /** Ya ocurrio la primera compra del referido: se dispara el premio. */
    case Purchased = 'purchased';

    /** Rechazado por antifraude (auto-referido, ya era cliente, etc.). */
    case Rejected = 'rejected';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
