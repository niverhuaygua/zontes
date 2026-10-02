<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Support;

use App\Domain\Loyalty\Enums\CalcType;
use App\Domain\Loyalty\PointRule;

/**
 * Calcula los puntos de una regla a partir del monto del evento.
 * Siempre devuelve enteros (nunca float) y redondea hacia abajo.
 */
final class PointsCalculator
{
    /**
     * @param  string|int|float|null  $amount  monto en Bs
     */
    public function calculate(PointRule $rule, string|int|float|null $amount): int
    {
        $amountMinor = $amount === null ? 0.0 : (float) $amount;

        if ($this->belowMinimum($rule, $amountMinor)) {
            return 0;
        }

        return match ($rule->calc_type) {
            CalcType::Fixed => $this->fixed($rule),
            CalcType::PerAmount => $this->perAmount($rule, $amountMinor),
            CalcType::Percent => $this->percent($rule, $amountMinor),
        };
    }

    private function belowMinimum(PointRule $rule, float $amount): bool
    {
        if ($rule->min_amount === null) {
            return false;
        }

        return $amount < (float) $rule->min_amount;
    }

    private function fixed(PointRule $rule): int
    {
        return $this->toInt((float) $rule->value);
    }

    /**
     * "X puntos por cada N Bs": value = N (Bs por paso) y
     * conditions.points_per_step = X (por defecto 1).
     */
    private function perAmount(PointRule $rule, float $amount): int
    {
        $step = (float) $rule->value;

        if ($step <= 0) {
            return 0;
        }

        $pointsPerStep = $this->toInt((float) ($rule->conditions['points_per_step'] ?? 1));

        return (int) floor($amount / $step) * $pointsPerStep;
    }

    private function percent(PointRule $rule, float $amount): int
    {
        $percent = (float) $rule->value;

        return $this->toInt($amount * $percent / 100);
    }

    private function toInt(float $value): int
    {
        return (int) floor(max(0.0, $value));
    }
}
