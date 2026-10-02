<?php

declare(strict_types=1);

namespace App\Domain\Customers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vehiculo de un cliente. No tiene brand_id: se alcanza siempre a traves de la
 * membresia, que ya esta limitada por el scope de marca.
 *
 * @property int $brand_member_id
 * @property string|null $vin
 */
class Vehicle extends Model
{
    protected $fillable = [
        'brand_member_id',
        'vin',
        'model',
        'purchase_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<BrandMember, $this>
     */
    public function brandMember(): BelongsTo
    {
        return $this->belongsTo(BrandMember::class);
    }
}
