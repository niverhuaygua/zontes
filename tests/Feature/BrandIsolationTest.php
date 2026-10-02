<?php

declare(strict_types=1);

use App\Domain\Brands\CurrentBrand;
use App\Domain\Customers\BrandMember;
use App\Domain\Customers\CustomerRecord;
use App\Domain\Loyalty\Actions\AwardPoints;
use App\Domain\Loyalty\Enums\CalcType;
use App\Domain\Loyalty\Enums\RuleEventType;
use App\Domain\Loyalty\PointLedger;
use App\Domain\Loyalty\PointRule;
use App\Models\Brand;

it('aplica el scope de marca en las consultas del portal', function (): void {
    $zontes = Brand::factory()->create(['slug' => 'zontes']);
    $niu = Brand::factory()->create(['slug' => 'niu']);

    $memberZontes = BrandMember::factory()->forBrand($zontes)->create();
    $memberNiu = BrandMember::factory()->forBrand($niu)->create();

    app(CurrentBrand::class)->set($zontes);

    expect(BrandMember::query()->pluck('id')->all())->toBe([$memberZontes->id]);

    app(CurrentBrand::class)->set($niu);

    expect(BrandMember::query()->pluck('id')->all())->toBe([$memberNiu->id])
        ->and(BrandMember::withoutGlobalScopes()->count())->toBe(2);
});

it('no muestra clientes precargados de otra marca', function (): void {
    $zontes = Brand::factory()->create(['slug' => 'zontes']);
    $niu = Brand::factory()->create(['slug' => 'niu']);

    CustomerRecord::factory()->create(['brand_id' => $zontes->id]);
    CustomerRecord::factory()->create(['brand_id' => $niu->id]);

    app(CurrentBrand::class)->set($zontes);

    expect(CustomerRecord::query()->count())->toBe(1)
        ->and(CustomerRecord::query()->first()->brand_id)->toBe($zontes->id);
});

it('mantiene billeteras independientes por marca', function (): void {
    $zontes = Brand::factory()->create(['slug' => 'zontes']);
    $niu = Brand::factory()->create(['slug' => 'niu']);

    $memberZontes = BrandMember::factory()->forBrand($zontes)->create();
    $memberNiu = BrandMember::factory()->forBrand($niu)->create();

    PointRule::factory()->create([
        'brand_id' => $zontes->id,
        'event_type' => RuleEventType::Purchase,
        'calc_type' => CalcType::Fixed,
        'value' => 100,
    ]);

    PointRule::factory()->create([
        'brand_id' => $niu->id,
        'event_type' => RuleEventType::Purchase,
        'calc_type' => CalcType::Fixed,
        'value' => 1,
    ]);

    app(AwardPoints::class)->handle($memberZontes, RuleEventType::Purchase, idempotencyKey: 'z-1');
    app(AwardPoints::class)->handle($memberNiu, RuleEventType::Purchase, idempotencyKey: 'n-1');

    expect($memberZontes->balance())->toBe(100)
        ->and($memberNiu->balance())->toBe(1)
        ->and(PointLedger::withoutGlobalScopes()->where('brand_member_id', $memberZontes->id)->sum('points'))->toBe(100)
        ->and(PointLedger::withoutGlobalScopes()->where('brand_member_id', $memberNiu->id)->sum('points'))->toBe(1);
});
