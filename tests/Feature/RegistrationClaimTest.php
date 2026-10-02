<?php

declare(strict_types=1);

use App\Domain\Customers\Actions\RegisterCustomer;
use App\Domain\Customers\CustomerRecord;
use App\Domain\Customers\Enums\ClaimStatus;
use App\Domain\Customers\Enums\MemberStatus;
use App\Models\Brand;

it('reclama un cliente precargado cuando coincide el segundo dato', function (): void {
    $brand = Brand::factory()->create();

    $record = CustomerRecord::withoutGlobalScopes()->create([
        'brand_id' => $brand->id,
        'ci' => '8000999',
        'first_name' => 'Pedro',
        'last_name' => 'Rojas',
        'email' => 'pedro@test.test',
        'phone' => '70001234',
        'claim_status' => ClaimStatus::Unclaimed,
    ]);

    $result = app(RegisterCustomer::class)->handle($brand, [
        'name' => 'Pedro Rojas',
        'email' => 'pedro@test.test',
        'password' => 'secret123',
        'ci' => '8000999',
        'phone' => '70001234',
    ]);

    expect($result['linked'])->toBeTrue()
        ->and($result['member']->status)->toBe(MemberStatus::Active)
        ->and($result['member']->brand_id)->toBe($brand->id)
        ->and($record->fresh()->claim_status)->toBe(ClaimStatus::Claimed)
        ->and($record->fresh()->claimed_by_user_id)->toBe($result['user']->id);
});

it('deja la membresia en revision si el segundo dato no coincide', function (): void {
    $brand = Brand::factory()->create();

    $record = CustomerRecord::withoutGlobalScopes()->create([
        'brand_id' => $brand->id,
        'ci' => '8000888',
        'first_name' => 'Lucia',
        'last_name' => 'Mendez',
        'email' => 'lucia@test.test',
        'phone' => '71112222',
        'claim_status' => ClaimStatus::Unclaimed,
    ]);

    $result = app(RegisterCustomer::class)->handle($brand, [
        'name' => 'Lucia Mendez',
        'email' => 'otra@test.test',
        'password' => 'secret123',
        'ci' => '8000888',
        'phone' => '79999999',
    ]);

    expect($result['linked'])->toBeFalse()
        ->and($result['member']->status)->toBe(MemberStatus::PendingReview)
        ->and($record->fresh()->claim_status)->toBe(ClaimStatus::PendingReview)
        ->and($record->fresh()->claimed_by_user_id)->toBeNull();
});

it('registra una membresia activa cuando el CI no estaba precargado', function (): void {
    $brand = Brand::factory()->create();

    $result = app(RegisterCustomer::class)->handle($brand, [
        'name' => 'Nuevo Cliente',
        'email' => 'nuevo@test.test',
        'password' => 'secret123',
        'ci' => '8000777',
    ]);

    expect($result['linked'])->toBeFalse()
        ->and($result['record'])->toBeNull()
        ->and($result['member']->status)->toBe(MemberStatus::Active);
});
