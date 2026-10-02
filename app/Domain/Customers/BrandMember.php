<?php

declare(strict_types=1);

namespace App\Domain\Customers;

use App\Domain\Brands\Concerns\BelongsToBrand;
use App\Domain\Customers\Enums\MemberStatus;
use App\Domain\Loyalty\PointLedger;
use App\Domain\Loyalty\PointWallet;
use App\Models\Brand;
use App\Models\User;
use Database\Factories\BrandMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Perfil de un usuario dentro de una marca y dueno de su billetera.
 *
 * @property int $id
 * @property int $user_id
 * @property int $brand_id
 * @property MemberStatus $status
 * @property string $referral_code
 */
class BrandMember extends Model
{
    use BelongsToBrand;

    /** @use HasFactory<BrandMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'brand_id',
        'status',
        'referral_code',
        'referred_by_member_id',
        'joined_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MemberStatus::class,
            'joined_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * @return HasMany<PointLedger, $this>
     */
    public function ledger(): HasMany
    {
        return $this->hasMany(PointLedger::class);
    }

    /**
     * @return HasOne<PointWallet, $this>
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(PointWallet::class);
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_member_id');
    }

    public function isActive(): bool
    {
        return $this->status === MemberStatus::Active;
    }

    public function balance(): int
    {
        return (int) ($this->wallet()->value('balance') ?? 0);
    }

    public static function generateReferralCode(?string $prefix = null): string
    {
        // Sin vocales ni caracteres ambiguos (0/O, 1/I/L) para dictarlo por telefono.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $length = (int) config('loyalty.referrals.code_length', 8);
        $max = strlen($alphabet) - 1;

        do {
            $random = '';
            for ($i = 0; $i < $length; $i++) {
                $random .= $alphabet[random_int(0, $max)];
            }

            $code = ($prefix !== null && $prefix !== '' ? mb_strtoupper($prefix).'-' : '').$random;
            $exists = self::withoutGlobalScopes()->where('referral_code', $code)->exists();
        } while ($exists);

        return $code;
    }
}
