<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Loyalty\Enums\CalcType;
use App\Domain\Loyalty\Enums\RuleEventType;
use App\Domain\Loyalty\PointRule;
use App\Models\Brand;
use Illuminate\Database\Seeder;

/**
 * Reglas de demostracion (los valores reales deben confirmarse con el cliente,
 * AGENTS.md §17). Cada marca tiene sus propias reglas.
 */
class PointRuleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Brand::query()->get() as $brand) {
            $rules = [
                [
                    'name' => '1 punto por cada Bs 10 en compras',
                    'event_type' => RuleEventType::Purchase,
                    'calc_type' => CalcType::PerAmount,
                    'value' => 10,
                    'conditions' => ['points_per_step' => 1],
                    'expires_after_days' => 365,
                ],
                [
                    'name' => '50 puntos por mantenimiento',
                    'event_type' => RuleEventType::Maintenance,
                    'calc_type' => CalcType::Fixed,
                    'value' => 50,
                    'expires_after_days' => 365,
                ],
                [
                    'name' => '100 puntos al registrarse un referido',
                    'event_type' => RuleEventType::ReferralRegistered,
                    'calc_type' => CalcType::Fixed,
                    'value' => 100,
                    'expires_after_days' => 365,
                ],
                [
                    'name' => '200 puntos por la primera compra del referido',
                    'event_type' => RuleEventType::ReferralPurchase,
                    'calc_type' => CalcType::Fixed,
                    'value' => 200,
                    'expires_after_days' => 365,
                ],
            ];

            foreach ($rules as $rule) {
                PointRule::withoutGlobalScopes()->updateOrCreate(
                    [
                        'brand_id' => $brand->getKey(),
                        'event_type' => $rule['event_type'],
                        'name' => $rule['name'],
                    ],
                    $rule + ['brand_id' => $brand->getKey(), 'active' => true],
                );
            }
        }
    }
}
