<?php

declare(strict_types=1);

namespace App\Domain\Imports\Support;

use App\Domain\Imports\Enums\ImportType;

/**
 * Mapea los encabezados reales del CSV a los nombres canonicos del dominio,
 * usando el mapeo configurable de config/loyalty.php. Agregar una variante de
 * encabezado no requiere tocar codigo.
 */
final class ColumnMapper
{
    /**
     * @param  array<string, int>  $headers  encabezados normalizados del CSV
     * @return array<string, string> canonico => encabezado real
     */
    public function resolve(ImportType $type, array $headers): array
    {
        $map = (array) config("loyalty.imports.column_map.{$type->value}", []);
        $resolved = [];

        foreach ($map as $canonical => $accepted) {
            foreach ((array) $accepted as $candidate) {
                $candidate = CsvReader::normalizeHeader((string) $candidate);

                if (array_key_exists($candidate, $headers)) {
                    $resolved[$canonical] = $candidate;
                    break;
                }
            }
        }

        return $resolved;
    }

    /**
     * Columnas canonicas obligatorias por tipo.
     *
     * @return array<int, string>
     */
    public function required(ImportType $type): array
    {
        return match ($type) {
            ImportType::Customers => ['ci', 'first_name', 'last_name'],
            ImportType::Purchases => ['ci', 'invoice_number', 'purchased_at', 'amount'],
            ImportType::Maintenances => ['ci', 'order_number', 'service_at'],
        };
    }

    /**
     * @param  array<string, int>  $headers
     * @return array<int, string> columnas obligatorias que faltan
     */
    public function missing(ImportType $type, array $headers): array
    {
        $resolved = $this->resolve($type, $headers);

        return array_values(array_diff($this->required($type), array_keys($resolved)));
    }
}
