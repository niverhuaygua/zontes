<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Customers\BrandMember;
use App\Domain\Customers\Enums\MemberStatus;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandMember>
 */
class BrandMemberFactory extends Factory
{
    protected $model = BrandMember::class;

    /* */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'brand_id' => Brand::factory(),
            'status' => MemberStatus::Active,
            'referral_code' => BrandMember::generateReferralCode(),
            'joined_at' => now(),
        ];
    }

    public function forBrand(Brand $brand): static
    {
        return $this->state(fn (): array => ['brand_id' => $brand->getKey()]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => ['user_id' => $user->getKey()]);
    }
}
