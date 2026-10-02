<?php

declare(strict_types=1);

namespace App\Domain\Loyalty;

use App\Domain\Customers\BrandMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cache del saldo por membresia. Se actualiza en la misma transaccion que el
 * movimiento del ledger y se puede reconstruir con points:reconcile.
 *
 * @property int $brand_member_id
 * @property int $balance
 */
class PointWallet extends Model
{
    protected $fillable = [
        'brand_member_id',
        'balance',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'integer',
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
