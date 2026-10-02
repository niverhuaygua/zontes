<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Customers\CiHasher;
use App\Domain\Customers\CustomerRecord;
use App\Domain\Customers\Enums\ClaimStatus;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerRecord>
 */
class CustomerRecordFactory extends Factory
{
    protected $model = CustomerRecord::class;

    /* */
    public function definition(): array
    {
        $ci = (string) fake()->unique()->numberBetween(1000000, 9999999);

        return [
            'brand_id' => Brand::factory(),
            'ci' => $ci,
            'ci_hash' => app(CiHasher::class)->hash($ci),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('7#######'),
            'registered_at' => now()->subMonths(3),
            'claim_status' => ClaimStatus::Unclaimed,
            'claimed_by_user_id' => null,
            'claimed_at' => null,
            'source_import_id' => null,
        ];
    }
}
