<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Customers\BrandMember;
use App\Domain\Customers\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Identidad global de una persona (una sola cuenta, membresias por marca).
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $ci
 * @property string|null $ci_hash
 * @property UserRole $role
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'ci',
        'ci_hash',
        'phone',
        'birth_date',
        'role',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ci' => 'encrypted',
            'phone' => 'encrypted',
            'birth_date' => 'encrypted',
            'role' => UserRole::class,
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Marcas en las que el usuario es administrador.
     *
     * @return BelongsToMany<Brand, $this>
     */
    public function administeredBrands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'brand_admin', 'user_id', 'brand_id')
            ->withTimestamps();
    }

    /**
     * @return HasMany<BrandMember, $this>
     */
    public function brandMembers(): HasMany
    {
        return $this->hasMany(BrandMember::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin() || $this->administeredBrands()->exists();
    }

    public function isBranchStaff(): bool
    {
        return $this->role->isStaff();
    }

    /**
     * Un administrador de marca solo opera las marcas que le fueron asignadas.
     */
    public function administersBrand(int|Brand $brand): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $brandId = $brand instanceof Brand ? $brand->getKey() : $brand;

        return $this->administeredBrands()->whereKey($brandId)->exists();
    }

    public function recordLogin(): void
    {
        $this->forceFill(['last_login_at' => now()])->save();
    }
}
