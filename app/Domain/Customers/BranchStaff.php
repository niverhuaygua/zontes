<?php

declare(strict_types=1);

namespace App\Domain\Customers;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asignacion de un usuario a una sucursal.
 *
 * @property int $user_id
 * @property int $branch_id
 */
class BranchStaff extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Branch, $this>
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function brandId(): int
    {
        return (int) ($this->branch()->value('brand_id') ?? 0);
    }

    public function servesBrand(Brand|int $brand): bool
    {
        $brandId = $brand instanceof Brand ? $brand->getKey() : $brand;

        return $this->branch()->where('brand_id', $brandId)->exists();
    }
}
