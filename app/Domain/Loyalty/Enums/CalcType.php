<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Enums;

enum CalcType: string
{
    /** X puntos fijos por evento. */
    case Fixed = 'fixed';

    /** X puntos por cada N Bs (redondeo hacia abajo). */
    case PerAmount = 'per_amount';

    /** Porcentaje del monto. */
    case Percent = 'percent';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
