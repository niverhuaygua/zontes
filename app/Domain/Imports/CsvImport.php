<?php

declare(strict_types=1);

namespace App\Domain\Imports;

use App\Domain\Brands\Concerns\BelongsToBrand;
use App\Domain\Imports\Enums\ImportStatus;
use App\Domain\Imports\Enums\ImportType;
use App\Models\Brand;
use App\Models\User;
use Database\Factories\CsvImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Carga de un archivo CSV: subir -> validar -> vista previa -> confirmar -> cola.
 *
 * @property int $brand_id
 * @property ImportType $type
 * @property ImportStatus $status
 * @property string $file_path
 */
class CsvImport extends Model
{
    use BelongsToBrand;

    /** @use HasFactory<CsvImportFactory> */
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'type',
        'filename',
        'file_path',
        'status',
        'column_map',
        'delimiter',
        'preview',
        'rows_total',
        'rows_ok',
        'rows_failed',
        'error_message',
        'user_id',
        'confirmed_at',
        'started_at',
        'finished_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ImportType::class,
            'status' => ImportStatus::class,
            'column_map' => 'array',
            'preview' => 'array',
            'confirmed_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<CsvImportError, $this>
     */
    public function errors(): HasMany
    {
        return $this->hasMany(CsvImportError::class);
    }

    public function absolutePath(): string
    {
        return Storage::disk((string) config('loyalty.imports.disk'))->path($this->file_path);
    }

    public function isProcessed(): bool
    {
        return in_array($this->status, [ImportStatus::Done, ImportStatus::Failed], true);
    }

    public function totalRows(): int
    {
        return (int) $this->rows_total + (int) $this->rows_ok + (int) $this->rows_failed;
    }
}
