<?php

declare(strict_types=1);

namespace App\Domain\Loyalty;

use App\Domain\Brands\Concerns\BelongsToBrand;
use App\Domain\Loyalty\Enums\CalcType;
use App\Domain\Loyalty\Enums\RuleEventType;
use App\Models\Brand;
use Database\Factories\PointRuleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Regla de acumulacion por marca y tipo de evento.
 *
 * @property int $brand_id
 * @property RuleEventType $event_type
 * @property CalcType $calc_type
 * @property string $value
 * @property string|null $min_amount
 * @property int|null $expires_after_days
 * @property bool $active
 */
class PointRule extends Model
{
    use BelongsToBrand;

    /** @use HasFactory<PointRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'name',
        'event_type',
        'calc_type',
        'value',
        'min_amount',
        'conditions',
        'expires_after_days',
        'active',
        'starts_at',
        'ends_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => RuleEventType::class,
            'calc_type' => CalcType::class,
            'value' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'conditions' => 'array',
            'expires_after_days' => 'integer',
            'active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Reglas vigentes de una marca para un tipo de evento en una fecha dada.
     *
     * @param  Builder<PointRule>  $query
     * @return Builder<PointRule>
     */
    public function scopeForEvent(Builder $query, RuleEventType|string $eventType, ?Carbon $at = null): Builder
    {
        $at ??= now();
        $eventType = $eventType instanceof RuleEventType ? $eventType->value : $eventType;

        return $query->where('event_type', $eventType)
            ->where('active', true)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $at));
    }

    /**
     * Dias de vigencia del lote; null significa que el lote no vence.
     */
    public function expiryDays(): ?int
    {
        return $this->expires_after_days ?? (int) config('loyalty.points.default_expiry_days');
    }
}
