<?php

declare(strict_types=1);

use App\Domain\Customers\BrandMember;
use App\Domain\Loyalty\Actions\ConsumePoints;
use App\Domain\Loyalty\Enums\LedgerType;
use App\Domain\Loyalty\PointLedger;
use App\Domain\Loyalty\PointWallet;
use App\Models\Brand;
use Illuminate\Support\Carbon;

function makeLot(BrandMember $member, int $points, ?Carbon $expiresAt, string $key): PointLedger
{
    return PointLedger::query()->create([
        'brand_member_id' => $member->getKey(),
        'type' => LedgerType::Earn,
        'points' => $points,
        'lot_remaining' => $points,
        'expires_at' => $expiresAt,
        'idempotency_key' => $key,
        'created_at' => now(),
    ]);
}

it('consume lotes en orden FIFO por vencimiento', function (): void {
    $brand = Brand::factory()->create();
    $member = BrandMember::factory()->forBrand($brand)->create();

    $early = makeLot($member, 10, Carbon::now()->addDays(10), 'lot-early');
    $late = makeLot($member, 10, Carbon::now()->addDays(30), 'lot-late');
    $never = makeLot($member, 10, null, 'lot-never');

    PointWallet::query()->create(['brand_member_id' => $member->id, 'balance' => 30]);

    $redeem = app(ConsumePoints::class)->handle(
        member: $member,
        points: 15,
        idempotencyKey: 'redeem-1',
    );

    expect($redeem->type)->toBe(LedgerType::Redeem)
        ->and($redeem->points)->toBe(-15)
        ->and($early->fresh()->lot_remaining)->toBe(0)
        ->and($late->fresh()->lot_remaining)->toBe(5)
        ->and($never->fresh()->lot_remaining)->toBe(10)
        ->and($member->balance())->toBe(15)
        ->and($redeem->meta['lots'][0]['ledger_id'])->toBe($early->id)
        ->and($redeem->meta['lots'][0]['points'])->toBe(10)
        ->and($redeem->meta['lots'][1]['ledger_id'])->toBe($late->id);
});

it('rechaza el canje cuando el saldo es insuficiente', function (): void {
    $brand = Brand::factory()->create();
    $member = BrandMember::factory()->forBrand($brand)->create();
    makeLot($member, 5, Carbon::now()->addDays(10), 'only-lot');
    PointWallet::query()->create(['brand_member_id' => $member->id, 'balance' => 5]);

    expect(fn () => app(ConsumePoints::class)->handle($member, 10))
        ->toThrow(RuntimeException::class);

    expect($member->balance())->toBe(5);
});

it('es idempotente sobre la misma clave de canje', function (): void {
    $brand = Brand::factory()->create();
    $member = BrandMember::factory()->forBrand($brand)->create();
    makeLot($member, 20, Carbon::now()->addDays(10), 'lot');
    PointWallet::query()->create(['brand_member_id' => $member->id, 'balance' => 20]);

    $first = app(ConsumePoints::class)->handle($member, 20, idempotencyKey: 'redeem-dup');
    $second = app(ConsumePoints::class)->handle($member, 20, idempotencyKey: 'redeem-dup');

    expect($second->id)->toBe($first->id)
        ->and($member->balance())->toBe(0)
        ->and(PointLedger::withoutGlobalScopes()->where('type', LedgerType::Redeem->value)->count())->toBe(1);
});

it('no consume lotes vencidos', function (): void {
    $brand = Brand::factory()->create();
    $member = BrandMember::factory()->forBrand($brand)->create();

    $expired = makeLot($member, 10, Carbon::now()->subDay(), 'expired');
    $valid = makeLot($member, 10, Carbon::now()->addDays(10), 'valid');
    PointWallet::query()->create(['brand_member_id' => $member->id, 'balance' => 20]);

    app(ConsumePoints::class)->handle($member, 10);

    expect($expired->fresh()->lot_remaining)->toBe(10)
        ->and($valid->fresh()->lot_remaining)->toBe(0);
});
