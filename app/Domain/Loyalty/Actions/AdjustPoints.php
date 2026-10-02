<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Actions;

use App\Domain\Customers\BrandMember;
use App\Domain\Loyalty\Enums\LedgerType;
use App\Domain\Loyalty\PointLedger;
use App\Domain\Loyalty\PointWallet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Ajuste manual de puntos desde el panel admin. Motivo obligatorio.
 *
 *  - Ajuste positivo: crea un lote (adjust) con el vencimiento por defecto.
 *  - Ajuste negativo: consume lotes FIFO y registra un movimiento "adjust"
 *    negativo, para que la billetera siga cuadrando con la suma de lotes.
 *
 * Toda la operacion queda auditada en el propio movimiento (created_by + note).
 */
final class AdjustPoints
{
    public function __construct(private readonly ConsumePoints $consumePoints) {}

    public function handle(
        BrandMember $member,
        int $points,
        string $note,
        int $createdBy,
    ): PointLedger {
        if ($points === 0) {
            throw new RuntimeException('El ajuste no puede ser cero.');
        }

        if ($points < 0) {
            return $this->consumePoints->handle(
                member: $member,
                points: abs($points),
                sourceType: 'manual_adjust',
                createdBy: $createdBy,
                note: $note,
                type: LedgerType::Adjust,
            );
        }

        return DB::transaction(function () use ($member, $points, $note, $createdBy): PointLedger {
            $days = (int) config('loyalty.points.default_expiry_days', 365);

            $ledger = PointLedger::query()->create([
                'brand_member_id' => $member->getKey(),
                'type' => LedgerType::Adjust,
                'points' => $points,
                'lot_remaining' => $points,
                'expires_at' => Carbon::now()->addDays($days),
                'idempotency_key' => 'adjust:'.$member->getKey().':'.now()->timestamp.':'.random_int(1000, 9999),
                'note' => $note,
                'created_by' => $createdBy,
                'created_at' => now(),
            ]);

            PointWallet::query()->firstOrCreate(
                ['brand_member_id' => $member->getKey()],
                ['balance' => 0],
            )->increment('balance', $points);

            return $ledger;
        });
    }
}
