<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Loyalty\Enums\CalcType;
use App\Domain\Loyalty\Enums\RuleEventType;
use App\Domain\Loyalty\PointRule;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PointRule>
 */
class PointRuleFactory extends Factory
{
    protected $model = PointRule::class;

    /* */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'name' => 'Regla '.fake()->unique()->numberBetween(1, 9999),
            'event_type' => RuleEventType::Purchase,
            'calc_type' => CalcType::PerAmount,
            'value' => 10,
            'min_amount' => null,
            'conditions' => null,
            'expires_after_days' => 365,
            'active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function fixed(int $points): static
    {
        return $this->state(fn (): array => [
            'calc_type' => CalcType::Fixed,
            'value' => $points,
        ]);
    }

    public function forEvent(RuleEventType $event): static
    {
        return $this->state(fn (): array => ['event_type' => $event]);
    }
}
