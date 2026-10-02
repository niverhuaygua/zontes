<?php

declare(strict_types=1);

namespace App\Domain\Customers;

/**
 * Normaliza y hashea la cedula de identidad.
 *
 * El CI se guarda cifrado (cast "encrypted") y ademas un HMAC-SHA256 en
 * "ci_hash", que es deterministico y permite buscar por igualdad sin exponer
 * el valor en claro. Ver docs/decisions/0001-ci-cifrado-y-hash.md
 */
final class CiHasher
{
    public function normalize(?string $ci): ?string
    {
        if ($ci === null) {
            return null;
        }

        $value = trim($ci);
        if ($value === '') {
            return null;
        }

        // Mayusculas sin acentos, sin espacios ni puntos.
        $value = (string) preg_replace('/\s+/u', '', $value);
        $value = str_replace('.', '', $value);
        $value = (string) preg_replace('/[^0-9A-Z\-]/', '', mb_strtoupper($value, 'UTF-8'));

        if ($value === '') {
            return null;
        }

        // Complemento: se separa el guion para normalizar "1234567-1a" -> "1234567-1A".
        if (str_contains($value, '-')) {
            [$base, $complement] = array_pad(explode('-', $value, 2), 2, '');
            $value = $base.'-'.mb_strtoupper($complement, 'UTF-8');
        }

        return $value;
    }

    public function hash(?string $ci): ?string
    {
        $normalized = $this->normalize($ci);

        if ($normalized === null) {
            return null;
        }

        return hash_hmac('sha256', $normalized, (string) config('loyalty.ci.hash_key'));
    }

    public function matches(?string $plain, ?string $hash): bool
    {
        if ($plain === null || $hash === null) {
            return false;
        }

        return hash_equals($hash, (string) $this->hash($plain));
    }
}
