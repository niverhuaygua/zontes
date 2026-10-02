<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Actions;

use App\Domain\Customers\BrandMember;
use App\Domain\Loyalty\Enums\LedgerType;
use App\Domain\Loyalty\Enums\RuleEventType;
use App\Domain\Loyalty\PointLedger;
use App\Domain\Loyalty\PointRule;
use App\Domain\Loyalty\PointWallet;
use App\Domain\Loyalty\Support\PointsCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Acumula puntos para una membresia:
 *
 *  1. Busca las reglas activas de la marca para el tipo de evento y vigentes
 *     en la fecha del evento (si hay varias, gana la mas especifica: la de
 *     starts_at mas reciente y, a igualdad, el id mayor).
 *  2. Calcula los puntos.
 *  3. Crea un lote "earn" con su propio expires_at y actualiza la billetera
 *     en la misma transaccion.
 *
 * La operacion es idempotente: si la idempotency_key ya existe, devuelve el
 * movimiento existente sin duplicar puntos.
 */
final class AwardPoints
{
    public function __construct(private readonly PointsCalculator $calculator) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function handle(
        BrandMember $member,
        RuleEventType $eventType,
        string|int|float|null $amount = null,
        ?Carbon $occurredAt = null,
        ?string $idempotencyKey = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?int $createdBy = null,
        array $meta = [],
        ?string $note = null,
    ): ?PointLedger {
        $occurredAt ??= now();
        $idempotencyKey ??= $this->defaultKey($member, $eventType, $sourceType, $sourceId, $occurredAt);

        return DB::transaction(function () use (
            $member, $eventType, $amount, $occurredAt, $idempotencyKey,
            $sourceType, $sourceId, $createdBy, $meta, $note
        ): ?PointLedger {
            if ($existing = $this->findExisting($idempotencyKey)) {
                return $existing;
            }

            $rule = $this->resolveRule($member, $eventType, $occurredAt);

            if ($rule === null) {
                return null;
            }

            $points = $this->calculator->calculate($rule, $amount);

            if ($points <= 0) {
                return null;
            }

            $ledger = PointLedger::query()->create([
                'brand_member_id' => $member->getKey(),
                'type' => LedgerType::Earn,
                'points' => $points,
                'lot_remaining' => $points,
                'expires_at' => $this->expiryDate($rule, $occurredAt),
                'rule_id' => $rule->getKey(),
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'idempotency_key' => $idempotencyKey,
                'note' => $note ?? $rule->name,
                'meta' => $meta === [] ? null : $meta,
                'created_by' => $createdBy,
                'created_at' => now(),
            ]);

            $this->walletFor($member)->increment('balance', $points);

            return $ledger;
        });
    }

    private function resolveRule(BrandMember $member, RuleEventType $eventType, Carbon $at): ?PointRule
    {
        return PointRule::withoutGlobalScopes()
            ->where('brand_id', $member->brand_id)
            ->forEvent($eventType, $at)
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->first();
    }

    private function expiryDate(PointRule $rule, Carbon $occurredAt): ?Carbon
    {
        $days = $rule->expiryDays();

        return $days === null ? null : $occurredAt->copy()->addDays($days);
    }

    private function walletFor(BrandMember $member): PointWallet
    {
        return PointWallet::query()->firstOrCreate(
            ['brand_member_id' => $member->getKey()],
            ['balance' => 0],
        );
    }

    private function findExisting(string $idempotencyKey): ?PointLedger
    {
        return PointLedger::withoutGlobalScopes()
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    private function defaultKey(
        BrandMember $member,
        RuleEventType $eventType,
        ?string $sourceType,
        ?int $sourceId,
        Carbon $occurredAt,
    ): string {
        return sprintf(
            '%s:%d:%s:%s',
            $eventType->value,
            $member->brand_id,
            $sourceType ?? 'na',
            $sourceId ?? $occurredAt->timestamp,
        );
    }
}
