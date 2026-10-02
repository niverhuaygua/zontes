<?php

declare(strict_types=1);

use App\Domain\Customers\BrandMember;
use App\Domain\Loyalty\Actions\AwardPoints;
use App\Domain\Loyalty\Actions\ReconcileWallet;
use App\Domain\Loyalty\Enums\CalcType;
use App\Domain\Loyalty\Enums\LedgerType;
use App\Domain\Loyalty\Enums\RuleEventType;
use App\Domain\Loyalty\PointLedger;
use App\Domain\Loyalty\PointRule;
use App\Models\Brand;
use Illuminate\Support\Carbon;

it('acumula puntos creando un lote con su vencimiento', function (): void {
    $brand = Brand::factory()->create();
    $member = BrandMember::factory()->forBrand($brand)->create();

    PointRule::factory()->create([
        'brand_id' => $brand->id,
        'event_type' => RuleEventType::Purchase,
        'calc_type' => CalcType::PerAmount,
        'value' => 10,
        'expires_after_days' => 365,
    ]);

    $occurredAt = Carbon::now()->subDays(5);

    $ledger = app(AwardPoints::class)->handle(
        member: $member,
        eventType: RuleEventType::Purchase,
        amount: 250,
        occurredAt: $occurredAt,
        idempotencyKey: 'purchase:test:F-1',
    );

    expect($ledger)->not->toBeNull()
        ->and($ledger->type)->toBe(LedgerType::Earn)
        ->and($ledger->points)->toBe(25)
        ->and($ledger->lot_remaining)->toBe(25)
        ->and($ledger->expires_at?->toDateString())->toBe($occurredAt->copy()->addDays(365)->toDateString())
        ->and($member->balance())->toBe(25);
});

it('no duplica puntos al reprocesar la misma clave de idempotencia', function (): void {
    $brand = Brand::factory()->create();
    $member = BrandMember::factory()->forBrand($brand)->create();

    PointRule::factory()->create([
        'brand_id' => $brand->id,
        'event_type' => RuleEventType::Purchase,
        'calc_type' => CalcType::Fixed,
        'value' => 40,
    ]);

    $first = app(AwardPoints::class)->handle($member, RuleEventType::Purchase, idempotencyKey: 'purchase:test:F-9');
    $second = app(AwardPoints::class)->handle($member, RuleEventType::Purchase, idempotencyKey: 'purchase:test:F-9');

    expect(PointLedger::withoutGlobalScopes()->count())->toBe(1)
        ->and($second?->id)->toBe($first?->id)
        ->and($member->balance())->toBe(40);
});

it('no acumula si no hay regla aplicable al evento', function (): void {
    $brand = Brand::factory()->create();
    $member = BrandMember::factory()->forBrand($brand)->create();

    $ledger = app(AwardPoints::class)->handle($member, RuleEventType::Purchase, amount: 100);

    expect($ledger)->toBeNull()
        ->and($member->balance())->toBe(0);
});

it('mantiene el saldo igual a la suma de los lotes vigentes', function (): void {
    $brand = Brand::factory()->create();
    $member = BrandMember::factory()->forBrand($brand)->create();

    PointRule::factory()->create([
        'brand_id' => $brand->id,
        'event_type' => RuleEventType::Purchase,
        'calc_type' => CalcType::Fixed,
        'value' => 30,
    ]);

    app(AwardPoints::class)->handle($member, RuleEventType::Purchase, idempotencyKey: 'a');
    app(AwardPoints::class)->handle($member, RuleEventType::Purchase, idempotencyKey: 'b');

    $result = app(ReconcileWallet::class)->handle($member);

    expect($result['expected'])->toBe(60)
        ->and($result['before'])->toBe(60)
        ->and($result['fixed'])->toBeFalse();
});
