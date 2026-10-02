<?php

declare(strict_types=1);

namespace App\Domain\Imports\Support;

use RuntimeException;

/**
 * Lee un CSV con separador autodetectado y primera fila de encabezados.
 * Los encabezados se normalizan (minusculas, sin acentos ni separadores) para
 * poder mapearlos de forma tolerante al formato real del cliente.
 */
final class CsvReader
{
    /** @var resource|null */
    private $handle = null;

    private string $delimiter = ',';

    public function open(string $path): self
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("No se pudo abrir el archivo CSV: {$path}");
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("No se pudo abrir el archivo CSV: {$path}");
        }

        $this->handle = $handle;
        $this->delimiter = $this->detectDelimiter($path);

        return $this;
    }

    public function close(): void
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }

        $this->handle = null;
    }

    public function delimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * Encabezados normalizados (canonico => indice de columna).
     *
     * @return array<string, int>
     */
    public function headers(): array
    {
        $this->assertOpen();
        rewind($this->handle);

        $raw = fgetcsv($this->handle, 0, $this->delimiter);

        if ($raw === false) {
            return [];
        }

        $headers = [];
        foreach ($raw as $index => $value) {
            $normalized = self::normalizeHeader((string) $value);
            if ($normalized !== '' && ! array_key_exists($normalized, $headers)) {
                $headers[$normalized] = $index;
            }
        }

        return $headers;
    }

    /**
     * Itera las filas como arreglos asociativos con el encabezado normalizado.
     * El callback recibe (numeroDeFila, fila) y puede devolver false para stop.
     *
     * @param  callable(int, array<string, string|null>): (void|bool)  $callback
     */
    public function eachRow(callable $callback): void
    {
        $this->assertOpen();
        rewind($this->handle);

        fgetcsv($this->handle, 0, $this->delimiter);
        $rowNumber = 1;
        $headers = $this->headers();

        while (($raw = fgetcsv($this->handle, 0, $this->delimiter)) !== false) {
            $rowNumber++;

            if ($this->isEmptyRow($raw)) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $name => $index) {
                $assoc[$name] = isset($raw[$index]) ? trim((string) $raw[$index]) : null;
            }

            if ($callback($rowNumber, $assoc) === false) {
                break;
            }
        }
    }

    /**
     * Primeras N filas para la vista previa.
     *
     * @return array<int, array<string, string|null>>
     */
    public function preview(int $limit): array
    {
        $rows = [];
        $this->eachRow(function (int $number, array $row) use (&$rows, $limit): bool {
            $rows[] = ['_row' => (string) $number] + $row;

            return count($rows) < $limit;
        });

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    public function rawHeaderLabels(): array
    {
        $this->assertOpen();
        rewind($this->handle);
        $raw = fgetcsv($this->handle, 0, $this->delimiter);

        if ($raw === false) {
            return [];
        }

        return array_map(static fn ($value): string => trim((string) $value), $raw);
    }

    public static function normalizeHeader(string $header): string
    {
        $header = trim($header);
        $header = mb_strtolower($header, 'UTF-8');
        $header = strtr($header, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n',
        ]);
        $header = (string) preg_replace('/[^a-z0-9]+/', '_', $header);
        $header = (string) preg_replace('/^_+|_+$/', '', $header);

        // Quita el BOM UTF-8 si quedo al inicio.
        return ltrim($header, "\xEF\xBB\xBF");
    }

    private function detectDelimiter(string $path): string
    {
        $candidates = (array) config('loyalty.imports.delimiter_candidates', [',', ';']);
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return (string) config('loyalty.imports.default_delimiter', ',');
        }

        $sample = fgets($handle);
        fclose($handle);

        $sample = substr((string) $sample, 0, 4096);
        $best = (string) config('loyalty.imports.default_delimiter', ',');
        $bestCount = 0;

        foreach ($candidates as $candidate) {
            $count = substr_count($sample, (string) $candidate);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = (string) $candidate;
            }
        }

        return $best;
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function assertOpen(): void
    {
        if (! is_resource($this->handle)) {
            throw new RuntimeException('El archivo CSV no fue abierto.');
        }
    }
}
