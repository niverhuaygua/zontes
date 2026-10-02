<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Actions;

use App\Domain\Customers\BrandMember;
use App\Domain\Loyalty\Enums\LedgerType;
use App\Domain\Loyalty\PointLedger;
use App\Domain\Loyalty\PointWallet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Vence los lotes caducados: por cada lote con saldo y expires_at vencido crea
 * un movimiento "expire" negativo, deja lot_remaining = 0 y descuenta de la
 * billetera. Es idempotente porque el lote queda en 0 y la clave es
 * expire:{ledger_id}.
 */
final class ExpirePoints
{
    public function handle(?Carbon $at = null): int
    {
        $at ??= now();
        $expired = 0;

        PointLedger::withoutGlobalScopes()
            ->where('type', LedgerType::Earn->value)
            ->whereNotNull('lot_remaining')
            ->where('lot_remaining', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', $at)
            ->orderBy('id')
            ->chunkById(200, function ($lots) use ($at, &$expired): void {
                foreach ($lots as $lot) {
                    DB::transaction(function () use ($lot, $at, &$expired): void {
                        $remaining = (int) $lot->lot_remaining;

                        if ($remaining <= 0) {
                            return;
                        }

                        $key = 'expire:'.$lot->getKey();

                        if (PointLedger::withoutGlobalScopes()->where('idempotency_key', $key)->exists()) {
                            return;
                        }

                        $member = BrandMember::withoutGlobalScopes()->find($lot->brand_member_id);

                        if ($member === null) {
                            return;
                        }

                        PointLedger::query()->create([
                            'brand_member_id' => $member->getKey(),
                            'type' => LedgerType::Expire,
                            'points' => -$remaining,
                            'lot_remaining' => null,
                            'expires_at' => null,
                            'idempotency_key' => $key,
                            'note' => 'Vencimiento de lote #'.$lot->getKey(),
                            'meta' => ['ledger_id' => $lot->getKey()],
                            'created_at' => $at,
                        ]);

                        PointLedger::withoutGlobalScopes()
                            ->whereKey($lot->getKey())
                            ->update(['lot_remaining' => 0]);

                        PointWallet::query()
                            ->where('brand_member_id', $member->getKey())
                            ->decrement('balance', $remaining);

                        $expired++;
                    });
                }
            });

        return $expired;
    }
}
