<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Actions;

use App\Domain\Customers\BrandMember;
use App\Domain\Loyalty\Enums\LedgerType;
use App\Domain\Loyalty\PointLedger;
use App\Domain\Loyalty\PointWallet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Consume puntos de una membresia en un canje.
 *
 * Reglas:
 *  - lockForUpdate() sobre la billetera y sobre los lotes para evitar dobles
 *    gastos concurrentes (dos canjes con saldo para uno solo).
 *  - FIFO por vencimiento: primero los lotes que vencen antes; los lotes sin
 *    fecha de vencimiento van al final.
 *  - Un canje puede abarcar varios lotes; el detalle queda en meta.lots.
 *
 * Devuelve el movimiento "redeem" creado (puntos negativos).
 */
final class ConsumePoints
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function handle(
        BrandMember $member,
        int $points,
        ?string $idempotencyKey = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?int $createdBy = null,
        array $meta = [],
        ?string $note = null,
        LedgerType $type = LedgerType::Redeem,
    ): PointLedger {
        if ($points <= 0) {
            throw new RuntimeException('La cantidad de puntos a consumir debe ser mayor a cero.');
        }

        return DB::transaction(function () use (
            $member, $points, $idempotencyKey, $sourceType, $sourceId, $createdBy, $meta, $note, $type
        ): PointLedger {
            if ($idempotencyKey !== null) {
                $existing = PointLedger::withoutGlobalScopes()
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            $wallet = $this->lockWallet($member);

            if ($wallet->balance < $points) {
                throw new RuntimeException('Saldo insuficiente para realizar el canje.');
            }

            $lots = $this->lockOpenLots($member);

            $remaining = $points;
            $consumed = [];

            foreach ($lots as $lot) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min((int) $lot->lot_remaining, $remaining);
                $lot->lot_remaining = (int) $lot->lot_remaining - $take;
                PointLedger::withoutGlobalScopes()
                    ->whereKey($lot->getKey())
                    ->update(['lot_remaining' => $lot->lot_remaining]);

                $consumed[] = ['ledger_id' => $lot->getKey(), 'points' => $take];
                $remaining -= $take;
            }

            if ($remaining > 0) {
                // No deberia ocurrir: el saldo de la billetera debe cuadrar con
                // la suma de lotes. Se aborta para no consumir parcialmente.
                throw new RuntimeException('Los lotes vigentes no cubren el saldo de la billetera.');
            }

            $ledger = PointLedger::query()->create([
                'brand_member_id' => $member->getKey(),
                'type' => $type,
                'points' => -$points,
                'lot_remaining' => null,
                'expires_at' => null,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'idempotency_key' => $idempotencyKey ?? $type->value.':'.$member->getKey().':'.now()->timestamp.':'.random_int(1000, 9999),
                'note' => $note,
                'meta' => ['lots' => $consumed] + $meta,
                'created_by' => $createdBy,
                'created_at' => now(),
            ]);

            $wallet->decrement('balance', $points);

            return $ledger;
        });
    }

    private function lockWallet(BrandMember $member): PointWallet
    {
        $wallet = PointWallet::query()
            ->where('brand_member_id', $member->getKey())
            ->lockForUpdate()
            ->first();

        if ($wallet !== null) {
            return $wallet;
        }

        return PointWallet::query()->create([
            'brand_member_id' => $member->getKey(),
            'balance' => 0,
        ]);
    }

    /**
     * @return Collection<int, PointLedger>
     */
    private function lockOpenLots(BrandMember $member): Collection
    {
        return PointLedger::withoutGlobalScopes()
            ->where('brand_member_id', $member->getKey())
            ->where('type', LedgerType::Earn->value)
            ->whereNotNull('lot_remaining')
            ->where('lot_remaining', '>', 0)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now()))
            ->orderByRaw('expires_at IS NULL ASC')
            ->orderBy('expires_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }
}
