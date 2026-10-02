<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    /* */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(10, 99),
            'name' => $name,
            'short_name' => strtoupper(substr($name, 0, 3)),
            'theme' => [
                '--brand-primary' => '#b91c1c',
                '--brand-primary-dark' => '#7f1d1d',
                '--brand-accent' => '#f59e0b',
            ],
            'logo_path' => null,
            'currency' => 'BOB',
            'active' => true,
        ];
    }
}
