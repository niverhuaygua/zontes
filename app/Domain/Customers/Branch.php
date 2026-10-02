<?php

declare(strict_types=1);

namespace App\Domain\Customers;

use App\Domain\Brands\Concerns\BelongsToBrand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sucursal de una marca. Su personal valida cupones (Fase 3).
 *
 * @property int $brand_id
 * @property bool $active
 */
class Branch extends Model
{
    use BelongsToBrand;

    protected $fillable = [
        'brand_id',
        'name',
        'city',
        'address',
        'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<BranchStaff, $this>
     */
    public function staff(): HasMany
    {
        return $this->hasMany(BranchStaff::class);
    }
}
