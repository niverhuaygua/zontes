<?php

declare(strict_types=1);

namespace App\Domain\Imports\Jobs;

use App\Domain\Imports\Actions\ProcessImportRow;
use App\Domain\Imports\CsvImport;
use App\Domain\Imports\Enums\ImportStatus;
use App\Domain\Imports\Exceptions\ImportRowException;
use App\Domain\Imports\Support\ColumnMapper;
use App\Domain\Imports\Support\CsvReader;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Procesa el archivo CSV por lotes. Cada fila es independiente: una fila
 * invalida no aborta el archivo, se guarda en csv_import_errors.
 */
class ProcessCsvImport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public readonly int $importId) {}

    public function handle(
        ProcessImportRow $processor,
        ColumnMapper $mapper,
    ): void {
        /** @var CsvImport|null $import */
        $import = CsvImport::withoutGlobalScopes()->find($this->importId);

        if ($import === null) {
            return;
        }

        $import->update([
            'status' => ImportStatus::Processing,
            'started_at' => now(),
            'error_message' => null,
            'rows_ok' => 0,
            'rows_failed' => 0,
        ]);

        $reader = new CsvReader;

        try {
            $reader->open($import->absolutePath());
            $headers = $reader->headers();

            $missing = $mapper->missing($import->type, $headers);

            if ($missing !== []) {
                throw new ImportRowException('Faltan columnas obligatorias: '.implode(', ', $missing).'.');
            }

            $map = $mapper->resolve($import->type, $headers);
            $import->update(['column_map' => $map, 'delimiter' => $reader->delimiter()]);

            $total = 0;
            $ok = 0;
            $failed = 0;

            $reader->eachRow(function (int $rowNumber, array $row) use ($import, $map, $processor, &$total, &$ok, &$failed): void {
                $total++;
                $canonical = $this->canonicalRow($row, $map);

                try {
                    $processor->handle($import, $canonical);
                    $ok++;
                } catch (ImportRowException $e) {
                    $failed++;
                    $this->recordError($import, $rowNumber, $canonical, $e->getMessage());
                } catch (Throwable $e) {
                    $failed++;
                    $this->recordError($import, $rowNumber, $canonical, 'Error inesperado: '.$e->getMessage());
                }
            });

            $import->update([
                'status' => ImportStatus::Done,
                'rows_total' => $total,
                'rows_ok' => $ok,
                'rows_failed' => $failed,
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            $import->update([
                'status' => ImportStatus::Failed,
                'error_message' => mb_substr($e->getMessage(), 0, 250),
                'finished_at' => now(),
            ]);

            throw $e;
        } finally {
            $reader->close();
        }
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  array<string, string>  $map
     * @return array<string, string|null>
     */
    private function canonicalRow(array $row, array $map): array
    {
        $canonical = [];

        foreach ($map as $name => $header) {
            $canonical[$name] = $row[$header] ?? null;
        }

        return $canonical;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function recordError(CsvImport $import, int $rowNumber, array $row, string $message): void
    {
        $import->errors()->create([
            'row_number' => $rowNumber,
            'ci' => $row['ci'] ?? null,
            'message' => mb_substr($message, 0, 250),
            'raw' => $row,
        ]);
    }
}
