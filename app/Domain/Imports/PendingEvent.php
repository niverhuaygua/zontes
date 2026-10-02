<?php

declare(strict_types=1);

namespace App\Domain\Imports;

use App\Domain\Brands\Concerns\BelongsToBrand;
use App\Domain\Customers\CustomerRecord;
use App\Domain\Imports\Enums\PendingEventStatus;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Evento de negocio cargado por CSV para un cliente que aun no tiene cuenta.
 * Se guarda con la fecha original para que el lote de puntos respete el
 * vencimiento que tendria si se hubiera procesado el dia de la compra.
 *
 * @property int $brand_id
 * @property string $event_type
 * @property string|null $external_id
 * @property Carbon|null $occurred_at
 * @property PendingEventStatus $status
 */
class PendingEvent extends Model
{
    use BelongsToBrand;

    protected $fillable = [
        'brand_id',
        'customer_record_id',
        'event_type',
        'occurred_at',
        'external_id',
        'amount',
        'payload',
        'status',
        'credited_member_id',
        'source_import_id',
        'credited_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'amount' => 'decimal:2',
            'status' => PendingEventStatus::class,
            'credited_at' => 'datetime',
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
     * @return BelongsTo<CustomerRecord, $this>
     */
    public function customerRecord(): BelongsTo
    {
        return $this->belongsTo(CustomerRecord::class);
    }

    public function isPending(): bool
    {
        return $this->status === PendingEventStatus::Pending;
    }
}
