<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Customers\BrandMember;
use App\Domain\Customers\CiHasher;
use App\Domain\Customers\Enums\MemberStatus;
use App\Domain\Customers\Enums\UserRole;
use App\Domain\Loyalty\Actions\AwardPoints;
use App\Domain\Loyalty\Enums\RuleEventType;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Datos de demostracion de fidelizacion: clientes con membresias y saldo.
 * CSV de prueba ficticios (nunca datos reales, AGENTS.md §16).
 */
class DemoLoyaltySeeder extends Seeder
{
    public function run(): void
    {
        $hasher = app(CiHasher::class);
        $award = app(AwardPoints::class);

        $demos = [
            ['Zontes', 'zontes', 'María Fernández', 'maria@demo.test', '8000001', 1500.00],
            ['Zontes', 'zontes', 'Juan Pérez', 'juan@demo.test', '8000002', 320.00],
            ['NIU', 'niu', 'Lucía Rojas', 'lucia@demo.test', '8000003', 800.00],
        ];

        foreach ($demos as [$brandName, $slug, $name, $email, $ci, $amount]) {
            $brand = Brand::query()->where('slug', $slug)->first();

            if ($brand === null) {
                continue;
            }

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'ci' => $ci,
                    'ci_hash' => $hasher->hash($ci),
                    'role' => UserRole::Customer,
                    'email_verified_at' => now(),
                ],
            );

            $member = BrandMember::withoutGlobalScopes()->firstOrCreate(
                ['user_id' => $user->getKey(), 'brand_id' => $brand->getKey()],
                [
                    'status' => MemberStatus::Active,
                    'referral_code' => BrandMember::generateReferralCode($slug),
                    'joined_at' => now(),
                ],
            );

            $award->handle(
                member: $member,
                eventType: RuleEventType::Purchase,
                amount: $amount,
                occurredAt: now()->subDays(20),
                idempotencyKey: 'purchase:'.$brand->getKey().':DEMO-'.$ci,
                sourceType: 'seeder',
                note: "Compra demo {$brandName}",
            );
        }
    }
}
