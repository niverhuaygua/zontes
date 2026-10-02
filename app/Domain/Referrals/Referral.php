<?php

declare(strict_types=1);

namespace App\Domain\Referrals;

use App\Domain\Brands\Concerns\BelongsToBrand;
use App\Domain\Customers\BrandMember;
use App\Domain\Referrals\Enums\ReferralStatus;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Referido: un unico referidor por referido y marca.
 *
 * @property int $brand_id
 * @property int $referrer_member_id
 * @property int $referred_member_id
 * @property ReferralStatus $status
 */
class Referral extends Model
{
    use BelongsToBrand;

    protected $fillable = [
        'brand_id',
        'referrer_member_id',
        'referred_member_id',
        'status',
        'registered_at',
        'purchased_at',
        'reject_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReferralStatus::class,
            'registered_at' => 'datetime',
            'purchased_at' => 'datetime',
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
     * @return BelongsTo<BrandMember, $this>
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(BrandMember::class, 'referrer_member_id');
    }

    /**
     * @return BelongsTo<BrandMember, $this>
     */
    public function referred(): BelongsTo
    {
        return $this->belongsTo(BrandMember::class, 'referred_member_id');
    }
}
