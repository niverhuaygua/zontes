<?php

declare(strict_types=1);

namespace App\Domain\Brands;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Scope global de marca. Se aplica automaticamente a todos los modelos que
 * usan el trait BelongsToBrand, salvo que se desactive de forma explicita con
 * withoutGlobalScope(BrandScope::class).
 */
final class BrandScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $brandId = app(CurrentBrand::class)->id();

        if ($brandId === null) {
            return;
        }

        $builder->where($model->qualifyColumn('brand_id'), $brandId);
    }
}
