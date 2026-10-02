<?php

declare(strict_types=1);

namespace App\Domain\Imports\Enums;

enum ImportStatus: string
{
    /** Archivo subido, esperando confirmacion del administrador. */
    case Pending = 'pending';

    /** Confirmado y encolado para procesar. */
    case Processing = 'processing';

    case Done = 'done';

    case Failed = 'failed';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
