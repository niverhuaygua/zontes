<?php

declare(strict_types=1);

namespace App\Domain\Imports\Enums;

use App\Domain\Loyalty\Enums\RuleEventType;

enum ImportType: string
{
    case Customers = 'customers';
    case Purchases = 'purchases';
    case Maintenances = 'maintenances';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    /**
     * Tipo de evento de dominio que genera la carga.
     */
    public function eventType(): ?string
    {
        return match ($this) {
            self::Customers => null,
            self::Purchases => RuleEventType::Purchase->value,
            self::Maintenances => RuleEventType::Maintenance->value,
        };
    }

    /**
     * Columna que aporta el identificador externo para la idempotencia.
     */
    public function externalIdColumn(): ?string
    {
        return match ($this) {
            self::Customers => null,
            self::Purchases => 'invoice_number',
            self::Maintenances => 'order_number',
        };
    }
}
