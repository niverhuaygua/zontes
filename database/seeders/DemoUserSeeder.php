<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Customers\Branch;
use App\Domain\Customers\BranchStaff;
use App\Domain\Customers\CiHasher;
use App\Domain\Customers\Enums\UserRole;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Usuarios de demostracion: un super_admin, un admin de Zontes y un staff de
 * sucursal. Credenciales solo para desarrollo (password: "password").
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $zontes = Brand::query()->where('slug', 'zontes')->first();

        $superAdmin = $this->user('Super Admin', 'admin@zontes.test', '9000001', UserRole::SuperAdmin);

        if ($zontes !== null) {
            $brandAdmin = $this->user('Admin Zontes', 'zontes.admin@zontes.test', '9000002', UserRole::BrandAdmin);
            $brandAdmin->administeredBrands()->syncWithoutDetaching([$zontes->getKey()]);

            $staff = $this->user('Staff Sucursal', 'staff@zontes.test', '9000003', UserRole::BranchStaff);

            $branch = Branch::withoutGlobalScopes()->updateOrCreate(
                ['brand_id' => $zontes->getKey(), 'name' => 'Sucursal Central La Paz'],
                ['city' => 'La Paz', 'active' => true],
            );

            BranchStaff::query()->updateOrCreate(
                ['user_id' => $staff->getKey(), 'branch_id' => $branch->getKey()],
                [],
            );
        }

        $this->command?->info('Super admin: '.$superAdmin->email.' / password');
    }

    private function user(string $name, string $email, string $ci, UserRole $role): User
    {
        $hasher = app(CiHasher::class);

        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'ci' => $ci,
                'ci_hash' => $hasher->hash($ci),
                'role' => $role,
                'email_verified_at' => now(),
            ],
        );
    }
}
