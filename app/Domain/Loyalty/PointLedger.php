<?php

declare(strict_types=1);

namespace App\Domain\Loyalty;

use App\Domain\Customers\BrandMember;
use App\Domain\Loyalty\Enums\LedgerType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Movimiento del libro de puntos. INMUTABLE: no se actualiza ni se borra.
 * La unica columna mutable es lot_remaining, que se decrementa al consumir un
 * lote en un canje.
 *
 * @property int $brand_member_id
 * @property LedgerType $type
 * @property int $points
 * @property int|null $lot_remaining
 * @property Carbon|null $expires_at
 * @property array<string, mixed>|null $meta
 */
class PointLedger extends Model
{
    protected $table = 'point_ledger';

    public const UPDATED_AT = null;

    protected $fillable = [
        'brand_member_id',
        'type',
        'points',
        'lot_remaining',
        'expires_at',
        'rule_id',
        'source_type',
        'source_id',
        'idempotency_key',
        'note',
        'meta',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LedgerType::class,
            'points' => 'integer',
            'lot_remaining' => 'integer',
            'expires_at' => 'datetime',
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<BrandMember, $this>
     */
    public function brandMember(): BelongsTo
    {
        return $this->belongsTo(BrandMember::class);
    }

    /**
     * @return BelongsTo<PointRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(PointRule::class);
    }

    /**
     * Lotes vigentes (acumulaciones con saldo disponible y no vencidas).
     *
     * @param  Builder<PointLedger>  $query
     * @return Builder<PointLedger>
     */
    public function scopeOpenLots(Builder $query, ?Carbon $at = null): Builder
    {
        $at ??= now();

        return $query->where('type', LedgerType::Earn->value)
            ->whereNotNull('lot_remaining')
            ->where('lot_remaining', '>', 0)
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', $at));
    }
}
