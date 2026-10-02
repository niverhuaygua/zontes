<?php

declare(strict_types=1);

namespace App\Domain\Brands\Concerns;

use App\Domain\Brands\BrandScope;
use App\Domain\Brands\CurrentBrand;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marca un modelo como perteneciente a una marca: agrega la relacion, el scope
 * global y un accessor para saber si es la marca activa.
 */
trait BelongsToBrand
{
    public static function bootBelongsToBrand(): void
    {
        static::addGlobalScope(new BrandScope);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function belongsToActiveBrand(): bool
    {
        return app(CurrentBrand::class)->isCurrent($this->brand_id ?? 0);
    }
}
