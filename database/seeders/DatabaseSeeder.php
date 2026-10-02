<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeders de demostracion. En produccion, ejecutar solo BrandSeeder y los
     * usuarios administradores reales.
     */
    public function run(): void
    {
        $this->call([
            BrandSeeder::class,
            PointRuleSeeder::class,
            DemoUserSeeder::class,
            DemoLoyaltySeeder::class,
        ]);
    }
}
