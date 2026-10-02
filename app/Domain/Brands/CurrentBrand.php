<?php

declare(strict_types=1);

namespace App\Domain\Brands;

use App\Models\Brand;
use RuntimeException;

/**
 * Marca activa de la peticion (singleton por request).
 *
 * La resuelve el middleware ResolveBrand por subdominio y, en desarrollo
 * local, por prefijo de ruta (/zontes). Ningun controlador debe deducir la
 * marca de otra forma.
 */
final class CurrentBrand
{
    private ?Brand $brand = null;

    public function set(?Brand $brand): void
    {
        $this->brand = $brand;
    }

    public function get(): ?Brand
    {
        return $this->brand;
    }

    public function id(): ?int
    {
        return $this->brand?->id;
    }

    public function slug(): ?string
    {
        return $this->brand?->slug;
    }

    /**
     * @throws RuntimeException si no hay marca activa en el contexto actual.
     */
    public function require(): Brand
    {
        if ($this->brand === null) {
            throw new RuntimeException('No hay una marca activa en el contexto actual.');
        }

        return $this->brand;
    }

    public function isCurrent(int|Brand $brand): bool
    {
        $id = $brand instanceof Brand ? $brand->getKey() : $brand;

        return $this->brand !== null && (int) $this->brand->getKey() === (int) $id;
    }
}
