<?php

declare(strict_types=1);

namespace App\Domain\Imports\Actions;

use App\Domain\Customers\BrandMember;
use App\Domain\Customers\CiHasher;
use App\Domain\Customers\CustomerRecord;
use App\Domain\Customers\Enums\ClaimStatus;
use App\Domain\Customers\Vehicle;
use App\Domain\Imports\CsvImport;
use App\Domain\Imports\Enums\ImportType;
use App\Domain\Imports\Enums\PendingEventStatus;
use App\Domain\Imports\Exceptions\ImportRowException;
use App\Domain\Imports\PendingEvent;
use App\Domain\Loyalty\Actions\AwardPoints;
use App\Domain\Loyalty\Enums\RuleEventType;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Procesa una fila canonica de un CSV.
 *
 *  - customers: upsert del cliente precargado por (brand_id, ci).
 *  - purchases / maintenances: si el cliente ya tiene cuenta acredita puntos
 *    (idempotente por numero de factura/orden); si aun no tiene cuenta guarda
 *    un pending_event que se acreditara al reclamar la cuenta, conservando la
 *    fecha original para el vencimiento del lote.
 */
final class ProcessImportRow
{
    public function __construct(
        private readonly CiHasher $hasher,
        private readonly AwardPoints $awardPoints,
    ) {}

    /**
     * @param  array<string, string|null>  $row
     */
    public function handle(CsvImport $import, array $row): void
    {
        match ($import->type) {
            ImportType::Customers => $this->importCustomer($import, $row),
            ImportType::Purchases => $this->importEvent($import, $row, RuleEventType::Purchase),
            ImportType::Maintenances => $this->importEvent($import, $row, RuleEventType::Maintenance),
        };
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importCustomer(CsvImport $import, array $row): void
    {
        $ci = $this->requireValue($row, 'ci');

        $record = CustomerRecord::withoutGlobalScopes()->firstOrNew([
            'brand_id' => $import->brand_id,
            'ci_hash' => $this->hasher->hash($ci),
        ]);

        $record->fill([
            'brand_id' => $import->brand_id,
            'ci' => $ci,
            'first_name' => $this->value($row, 'first_name'),
            'last_name' => $this->value($row, 'last_name'),
            'email' => $this->value($row, 'email'),
            'phone' => $this->value($row, 'phone'),
            'registered_at' => $this->parseDate($row, 'registered_at', false),
            'source_import_id' => $import->getKey(),
        ]);

        if ($record->claim_status === null) {
            $record->claim_status = ClaimStatus::Unclaimed;
        }

        $record->save();
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importEvent(CsvImport $import, array $row, RuleEventType $eventType): void
    {
        $ci = $this->requireValue($row, 'ci');
        $externalId = $eventType === RuleEventType::Purchase
            ? $this->requireValue($row, 'invoice_number')
            : $this->requireValue($row, 'order_number');

        $occurredAt = $eventType === RuleEventType::Purchase
            ? $this->parseDate($row, 'purchased_at', true)
            : $this->parseDate($row, 'service_at', true);

        if ($occurredAt === null) {
            throw new ImportRowException('No se pudo interpretar la fecha del evento.');
        }

        $amount = $eventType === RuleEventType::Purchase
            ? $this->parseAmount($row, 'amount')
            : null;

        $member = $this->findMember($import, $ci);

        if ($member !== null) {
            $this->awardPoints->handle(
                member: $member,
                eventType: $eventType,
                amount: $amount,
                occurredAt: $occurredAt,
                idempotencyKey: $eventType->value.':'.$import->brand_id.':'.$externalId,
                sourceType: 'csv_import',
                sourceId: (int) $import->getKey(),
                meta: ['external_id' => $externalId],
            );

            $this->touchVehicle($member, $row);

            return;
        }

        $this->storePendingEvent($import, $row, $eventType, $externalId, $occurredAt, $amount);
    }

    private function findMember(CsvImport $import, string $ci): ?BrandMember
    {
        $hash = $this->hasher->hash($ci);

        $user = User::query()->where('ci_hash', $hash)->first();

        if ($user === null) {
            return null;
        }

        return BrandMember::withoutGlobalScopes()
            ->where('user_id', $user->getKey())
            ->where('brand_id', $import->brand_id)
            ->first();
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function storePendingEvent(
        CsvImport $import,
        array $row,
        RuleEventType $eventType,
        string $externalId,
        Carbon $occurredAt,
        ?string $amount,
    ): void {
        $record = CustomerRecord::withoutGlobalScopes()->firstOrCreate(
            [
                'brand_id' => $import->brand_id,
                'ci_hash' => $this->hasher->hash($this->requireValue($row, 'ci')),
            ],
            [
                'ci' => $this->requireValue($row, 'ci'),
                'first_name' => $this->value($row, 'first_name'),
                'last_name' => $this->value($row, 'last_name'),
                'claim_status' => ClaimStatus::Unclaimed,
                'source_import_id' => $import->getKey(),
            ],
        );

        $exists = PendingEvent::withoutGlobalScopes()
            ->where('brand_id', $import->brand_id)
            ->where('event_type', $eventType->value)
            ->where('external_id', $externalId)
            ->exists();

        if ($exists) {
            return;
        }

        PendingEvent::withoutGlobalScopes()->create([
            'brand_id' => $import->brand_id,
            'customer_record_id' => $record->getKey(),
            'event_type' => $eventType->value,
            'occurred_at' => $occurredAt,
            'external_id' => $externalId,
            'amount' => $amount,
            'payload' => $this->value($row, 'model'),
            'status' => PendingEventStatus::Pending,
            'source_import_id' => $import->getKey(),
        ]);
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function touchVehicle(BrandMember $member, array $row): void
    {
        $vin = $this->value($row, 'vin');

        if ($vin === null) {
            return;
        }

        Vehicle::query()->updateOrCreate(
            ['brand_member_id' => $member->getKey(), 'vin' => $vin],
            ['model' => $this->value($row, 'model')],
        );
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function requireValue(array $row, string $column): string
    {
        $value = $this->value($row, $column);

        if ($value === null) {
            throw new ImportRowException("Falta el valor obligatorio '{$column}'.");
        }

        return $value;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function value(array $row, string $column): ?string
    {
        $value = $row[$column] ?? null;

        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function parseDate(array $row, string $column, bool $required): ?Carbon
    {
        $value = $this->value($row, $column);

        if ($value === null) {
            if ($required) {
                throw new ImportRowException("Falta la fecha obligatoria '{$column}'.");
            }

            return null;
        }

        try {
            $date = Carbon::parse($value);
        } catch (\Throwable) {
            throw new ImportRowException("La fecha '{$column}' no es valida: {$value}.");
        }

        if ($date->isFuture()) {
            throw new ImportRowException("La fecha '{$column}' no puede ser futura: {$value}.");
        }

        return $date;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function parseAmount(array $row, string $column): string
    {
        $value = $this->value($row, $column);

        if ($value === null) {
            throw new ImportRowException("Falta el monto obligatorio '{$column}'.");
        }

        $normalized = $this->normalizeNumber($value);

        if ($normalized === null) {
            throw new ImportRowException("El monto no es numerico: {$value}.");
        }

        if ((float) $normalized <= 0) {
            throw new ImportRowException('El monto debe ser mayor a cero.');
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    /**
     * Acepta formatos "1234.56", "1234,56" y "1.234,56".
     */
    private function normalizeNumber(string $value): ?string
    {
        $value = str_replace([' ', 'Bs', 'Bs.'], '', $value);

        $hasDot = str_contains($value, '.');
        $hasComma = str_contains($value, ',');

        if ($hasDot && $hasComma) {
            // El ultimo separador visible es el decimal.
            $decimal = strrpos($value, ',') > strrpos($value, '.') ? ',' : '.';
            $thousands = $decimal === ',' ? '.' : ',';
            $value = str_replace($thousands, '', $value);
            $value = str_replace($decimal, '.', $value);
        } elseif ($hasComma) {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? $value : null;
    }
}
