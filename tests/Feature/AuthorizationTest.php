<?php

declare(strict_types=1);

use App\Domain\Customers\Enums\UserRole;
use App\Models\Brand;
use App\Models\User;

it('redirige a invitados al login desde el panel admin', function (): void {
    $this->get('/admin')->assertRedirect(route('login'));
});

it('prohibe el panel admin a un cliente', function (): void {
    $user = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('permite el panel admin a un super_admin', function (): void {
    $user = User::factory()->create(['role' => UserRole::SuperAdmin]);

    $this->actingAs($user)->get('/admin')->assertOk();
});

it('permite el panel admin a un brand_admin de su marca', function (): void {
    $brand = Brand::factory()->create();
    $user = User::factory()->create(['role' => UserRole::BrandAdmin]);
    $user->administeredBrands()->attach($brand);

    $this->actingAs($user)->get('/admin')->assertOk();
});

it('solo el super_admin puede gestionar marcas', function (): void {
    $brand = Brand::factory()->create();

    $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $brandAdmin = User::factory()->create(['role' => UserRole::BrandAdmin]);
    $brandAdmin->administeredBrands()->attach($brand);

    expect($superAdmin->can('create', Brand::class))->toBeTrue()
        ->and($brandAdmin->can('create', Brand::class))->toBeFalse()
        ->and($brandAdmin->can('update', $brand))->toBeTrue()
        ->and($brandAdmin->can('delete', $brand))->toBeFalse();
});

it('un brand_admin no gestiona a otros administradores', function (): void {
    $brandAdmin = User::factory()->create(['role' => UserRole::BrandAdmin]);

    $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    expect($brandAdmin->can('update', $superAdmin))->toBeFalse()
        ->and($brandAdmin->can('update', $customer))->toBeTrue()
        ->and($brandAdmin->can('delete', $customer))->toBeFalse();
});
