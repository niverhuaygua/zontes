<?php

declare(strict_types=1);

namespace App\Domain\Imports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Error de una fila del CSV. Una fila invalida no aborta el archivo.
 *
 * @property int $csv_import_id
 * @property int $row_number
 * @property string $message
 */
class CsvImportError extends Model
{
    protected $fillable = [
        'csv_import_id',
        'row_number',
        'ci',
        'message',
        'raw',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'row_number' => 'integer',
            'raw' => 'array',
        ];
    }

    /**
     * @return BelongsTo<CsvImport, $this>
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(CsvImport::class, 'csv_import_id');
    }
}
