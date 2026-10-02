<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Brands\CurrentBrand;
use App\Models\Brand;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Determina la marca activa de la peticion.
 *
 * 1. Subdominio: zontes.dominio.bo -> slug "zontes".
 * 2. Prefijo de ruta (desarrollo local): /zontes/ruta.
 * 3. Marca por defecto del portal si el usuario no tiene membresia en la
 *    marca resuelta; en ese caso se guarda la primera membresia activa.
 */
class ResolveBrand
{
    public function __construct(private readonly CurrentBrand $currentBrand) {}

    public function handle(Request $request, Closure $next): Response
    {
        $brand = $this->resolveFromSubdomain($request) ?? $this->resolveFromRoutePrefix($request);

        $this->currentBrand->set($brand);
        app('view')->share('currentBrand', $brand);
        app('view')->share('brandThemeCss', $brand?->themeVariables() ?? []);

        return $next($request);
    }

    private function resolveFromSubdomain(Request $request): ?Brand
    {
        $host = (string) $request->getHost();
        $baseDomain = (string) config('app.base_domain');

        if ($baseDomain === '' || ! str_ends_with($host, '.'.$baseDomain)) {
            return null;
        }

        $subdomain = substr($host, 0, -1 * (strlen($baseDomain) + 1));

        return $this->findActiveBrand($subdomain);
    }

    private function resolveFromRoutePrefix(Request $request): ?Brand
    {
        $slug = (string) $request->route()?->parameter('brand', '');

        if ($slug === '' || $slug === 'c') {
            return null;
        }

        return $this->findActiveBrand($slug);
    }

    private function findActiveBrand(string $slug): ?Brand
    {
        $slug = Brand::slugFor($slug);

        return Brand::query()->where('slug', $slug)->where('active', true)->first();
    }
}
