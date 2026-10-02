<?php

declare(strict_types=1);

namespace App\Domain\Customers;

use App\Domain\Brands\Concerns\BelongsToBrand;
use App\Domain\Customers\Enums\ClaimStatus;
use App\Domain\Imports\PendingEvent;
use App\Models\Brand;
use App\Models\User;
use Database\Factories\CustomerRecordFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cliente precargado por CSV que aun no tiene cuenta en el portal. El vinculo
 * con la cuenta se hace por CI (ver ClaimCustomerRecord).
 *
 * @property int $brand_id
 * @property string $ci
 * @property string $ci_hash
 * @property ClaimStatus $claim_status
 */
class CustomerRecord extends Model
{
    use BelongsToBrand;

    /** @use HasFactory<CustomerRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'ci',
        'ci_hash',
        'first_name',
        'last_name',
        'email',
        'phone',
        'registered_at',
        'claim_status',
        'claimed_by_user_id',
        'claimed_at',
        'source_import_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'registered_at' => 'datetime',
            'claimed_at' => 'datetime',
            'claim_status' => ClaimStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $record): void {
            $hasher = app(CiHasher::class);

            if ($record->isDirty('ci')) {
                $record->ci = $hasher->normalize($record->ci);
                $record->ci_hash = $hasher->hash($record->ci);
            }
        });
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function claimedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }

    /**
     * @return HasMany<PendingEvent, $this>
     */
    public function pendingEvents(): HasMany
    {
        return $this->hasMany(PendingEvent::class);
    }

    public function fullName(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
    }

    public function isUnclaimed(): bool
    {
        return $this->claim_status === ClaimStatus::Unclaimed;
    }

    /**
     * @return Attribute<string, never>
     */
    protected function initialName(): Attribute
    {
        return Attribute::get(fn (): string => mb_substr($this->fullName(), 0, 1));
    }
}
