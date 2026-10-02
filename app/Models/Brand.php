<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Brands\CurrentBrand;
use App\Domain\Customers\Branch;
use App\Domain\Customers\BrandMember;
use App\Domain\Loyalty\PointRule;
use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Marca de motos. El tema (colores, tipografia) vive en "theme" como json y se
 * inyecta en el layout como variables CSS: agregar una marca nueva es un
 * registro en esta tabla, sin tocar codigo.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property array<string, string>|null $theme
 * @property string $currency
 * @property bool $active
 */
class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    protected $table = 'brands';

    protected $fillable = [
        'slug',
        'name',
        'short_name',
        'theme',
        'logo_path',
        'currency',
        'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'theme' => 'array',
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<BrandMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(BrandMember::class);
    }

    /**
     * @return HasMany<PointRule, $this>
     */
    public function pointRules(): HasMany
    {
        return $this->hasMany(PointRule::class);
    }

    /**
     * @return HasMany<Branch, $this>
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Variable CSS del tema con valor por defecto.
     *
     * @return array<string, string>
     */
    public function themeVariables(): array
    {
        $defaults = [
            '--brand-primary' => '#b91c1c',
            '--brand-primary-dark' => '#7f1d1d',
            '--brand-accent' => '#f59e0b',
        ];

        return array_merge($defaults, $this->theme ?? []);
    }

    public static function slugFor(string $value): string
    {
        return Str::slug(Str::lower(trim($value)));
    }

    public function isActiveBrand(): bool
    {
        return app(CurrentBrand::class)->isCurrent($this);
    }
}
