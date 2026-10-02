<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

/**
 * Las tres marcas del programa. Agregar una marca nueva es solo un registro
 * mas: no se toca codigo (el tema se inyecta como variables CSS).
 */
class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            [
                'slug' => 'zontes',
                'name' => 'Zontes',
                'short_name' => 'ZON',
                'theme' => [
                    '--brand-primary' => '#b91c1c',
                    '--brand-primary-dark' => '#7f1d1d',
                    '--brand-accent' => '#f59e0b',
                ],
            ],
            [
                'slug' => 'kiden',
                'name' => 'Kiden',
                'short_name' => 'KID',
                'theme' => [
                    '--brand-primary' => '#1d4ed8',
                    '--brand-primary-dark' => '#1e3a8a',
                    '--brand-accent' => '#22d3ee',
                ],
            ],
            [
                'slug' => 'niu',
                'name' => 'NIU',
                'short_name' => 'NIU',
                'theme' => [
                    '--brand-primary' => '#16a34a',
                    '--brand-primary-dark' => '#14532d',
                    '--brand-accent' => '#facc15',
                ],
            ],
        ];

        foreach ($brands as $data) {
            Brand::query()->updateOrCreate(
                ['slug' => $data['slug']],
                $data + ['currency' => 'BOB', 'active' => true],
            );
        }
    }
}
