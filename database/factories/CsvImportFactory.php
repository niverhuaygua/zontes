<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Imports\CsvImport;
use App\Domain\Imports\Enums\ImportStatus;
use App\Domain\Imports\Enums\ImportType;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CsvImport>
 */
class CsvImportFactory extends Factory
{
    protected $model = CsvImport::class;

    /* */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'type' => ImportType::Customers,
            'filename' => 'clientes.csv',
            'file_path' => 'imports/clientes.csv',
            'status' => ImportStatus::Pending,
            'column_map' => null,
            'delimiter' => ',',
            'preview' => null,
            'rows_total' => 0,
            'rows_ok' => 0,
            'rows_failed' => 0,
            'user_id' => null,
            'confirmed_at' => null,
            'started_at' => null,
            'finished_at' => null,
        ];
    }
}
