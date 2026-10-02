<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Imports\Actions\ProcessImportRow;
use App\Domain\Imports\CsvImport;
use App\Domain\Imports\Enums\ImportStatus;
use App\Domain\Imports\Enums\ImportType;
use App\Domain\Imports\Jobs\ProcessCsvImport;
use App\Domain\Imports\Support\ColumnMapper;
use App\Domain\Imports\Support\CsvReader;
use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportsProcessCommand extends Command
{
    protected $signature = 'imports:process {file : Ruta del archivo CSV}
                            {--type= : customers|purchases|maintenances}
                            {--brand= : slug de la marca}
                            {--sync : Procesa en el acto, sin encolar}';

    protected $description = 'Carga un CSV por consola y lo procesa (clientes, compras o mantenimientos).';

    public function handle(ColumnMapper $mapper): int
    {
        $path = (string) $this->argument('file');
        $type = ImportType::tryFrom((string) $this->option('type'));
        $brandSlug = (string) $this->option('brand');

        if ($type === null) {
            $this->error('Debes indicar --type con customers, purchases o maintenances.');

            return self::FAILURE;
        }

        $brand = Brand::withoutGlobalScopes()->where('slug', $brandSlug)->first();

        if ($brand === null) {
            $this->error("No existe una marca con slug '{$brandSlug}'.");

            return self::FAILURE;
        }

        if (! is_file($path)) {
            $this->error("No existe el archivo {$path}.");

            return self::FAILURE;
        }

        $stored = 'imports/'.now()->format('Ymd_His').'_'.Str::slug((string) $type->value).'.csv';
        Storage::disk((string) config('loyalty.imports.disk'))->put($stored, (string) file_get_contents($path));

        $import = CsvImport::withoutGlobalScopes()->create([
            'brand_id' => $brand->getKey(),
            'type' => $type,
            'filename' => basename($path),
            'file_path' => $stored,
            'status' => ImportStatus::Pending,
            'confirmed_at' => now(),
        ]);

        $reader = (new CsvReader)->open($import->absolutePath());
        $missing = $mapper->missing($type, $reader->headers());
        $reader->close();

        if ($missing !== []) {
            $import->update([
                'status' => ImportStatus::Failed,
                'error_message' => 'Faltan columnas obligatorias: '.implode(', ', $missing).'.',
                'finished_at' => now(),
            ]);

            $this->error($import->error_message);

            return self::FAILURE;
        }

        if ((bool) $this->option('sync')) {
            (new ProcessCsvImport((int) $import->getKey()))->handle(app(ProcessImportRow::class), $mapper);
        } else {
            ProcessCsvImport::dispatch((int) $import->getKey());
        }

        $import->refresh();
        $this->info(sprintf(
            'Importacion #%d [%s]: %d filas, %d ok, %d con error.',
            $import->getKey(),
            $import->status->value,
            $import->rows_total,
            $import->rows_ok,
            $import->rows_failed,
        ));

        return $import->status === ImportStatus::Done ? self::SUCCESS : self::FAILURE;
    }
}
