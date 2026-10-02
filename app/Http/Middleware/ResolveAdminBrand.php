<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Brands\CurrentBrand;
use App\Models\Brand;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve la marca activa en el panel admin (que no usa prefijo de marca):
 * toma la marca guardada en sesion o, si no hay, la primera que el usuario
 * administra. El super_admin puede cambiar de marca con el conmutador.
 */
class ResolveAdminBrand
{
    public function __construct(private readonly CurrentBrand $currentBrand) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null && $user->isAdmin()) {
            $brand = $this->resolveBrand($user, $request);
            $this->currentBrand->set($brand);
            app('view')->share('currentBrand', $brand);
            app('view')->share('brandThemeCss', $brand?->themeVariables() ?? []);
        }

        return $next($request);
    }

    private function resolveBrand(User $user, Request $request): ?Brand
    {
        $brands = $this->availableBrands($user);

        if ($brands->isEmpty()) {
            return null;
        }

        $selected = (int) $request->session()->get('admin_brand_id', 0);

        return $brands->firstWhere('id', $selected) ?? $brands->first();
    }

    /**
     * @return Collection<int, Brand>
     */
    private function availableBrands(User $user): Collection
    {
        if ($user->isSuperAdmin()) {
            return Brand::query()->orderBy('name')->get();
        }

        return $user->administeredBrands()->orderBy('name')->get();
    }
}
