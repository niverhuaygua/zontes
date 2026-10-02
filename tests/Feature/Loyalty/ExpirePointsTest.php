<?php

declare(strict_types=1);

use App\Domain\Customers\BrandMember;
use App\Domain\Loyalty\Actions\AwardPoints;
use App\Domain\Loyalty\Actions\ExpirePoints;
use App\Domain\Loyalty\Enums\CalcType;
use App\Domain\Loyalty\Enums\LedgerType;
use App\Domain\Loyalty\Enums\RuleEventType;
use App\Domain\Loyalty\PointLedger;
use App\Domain\Loyalty\PointRule;
use App\Domain\Loyalty\PointWallet;
use App\Models\Brand;

it('vence solo los lotes caducados y descuenta la billetera', function (): void {
    $brand = Brand::factory()->create();
    $member = BrandMember::factory()->forBrand($brand)->create();

    PointRule::factory()->create([
        'brand_id' => $brand->id,
        'event_type' => RuleEventType::Purchase,
        'calc_type' => CalcType::Fixed,
        'value' => 100,
        'expires_after_days' => 30,
    ]);

    app(AwardPoints::class)->handle(
        member: $member,
        eventType: RuleEventType::Purchase,
        occurredAt: now()->subDays(60),
        idempotencyKey: 'old-lot',
    );

    app(AwardPoints::class)->handle(
        member: $member,
        eventType: RuleEventType::Purchase,
        occurredAt: now()->subDay(),
        idempotencyKey: 'fresh-lot',
    );

    expect($member->balance())->toBe(200);

    $expired = app(ExpirePoints::class)->handle();

    expect($expired)->toBe(1)
        ->and($member->balance())->toBe(100);

    $oldLot = PointLedger::withoutGlobalScopes()->where('idempotency_key', 'old-lot')->first();
    $freshLot = PointLedger::withoutGlobalScopes()->where('idempotency_key', 'fresh-lot')->first();

    expect($oldLot?->lot_remaining)->toBe(0)
        ->and($freshLot?->lot_remaining)->toBe(100);

    expect(PointLedger::withoutGlobalScopes()->where('type', LedgerType::Expire->value)->count())->toBe(1);
});

it('es idempotente: ejecutarlo dos veces no duplica el vencimiento', function (): void {
    $brand = Brand::factory()->create();
    $member = BrandMember::factory()->forBrand($brand)->create();

    PointRule::factory()->create([
        'brand_id' => $brand->id,
        'event_type' => RuleEventType::Purchase,
        'calc_type' => CalcType::Fixed,
        'value' => 50,
        'expires_after_days' => 10,
    ]);

    app(AwardPoints::class)->handle(
        member: $member,
        eventType: RuleEventType::Purchase,
        occurredAt: now()->subDays(20),
        idempotencyKey: 'expired-lot',
    );

    expect(app(ExpirePoints::class)->handle())->toBe(1)
        ->and(app(ExpirePoints::class)->handle())->toBe(0)
        ->and(PointLedger::withoutGlobalScopes()->where('type', LedgerType::Expire->value)->count())->toBe(1)
        ->and($member->balance())->toBe(0);
});

it('no toca lotes sin vencimiento', function (): void {
    $brand = Brand::factory()->create();
    $member = BrandMember::factory()->forBrand($brand)->create();

    PointLedger::query()->create([
        'brand_member_id' => $member->id,
        'type' => LedgerType::Earn,
        'points' => 70,
        'lot_remaining' => 70,
        'expires_at' => null,
        'idempotency_key' => 'never-expires',
        'created_at' => now()->subYear(),
    ]);

    PointWallet::query()->create([
        'brand_member_id' => $member->id,
        'balance' => 70,
    ]);

    expect(app(ExpirePoints::class)->handle())->toBe(0)
        ->and($member->balance())->toBe(70);
});
