<?php

declare(strict_types=1);

namespace App\Domain\Loyalty\Actions;

use App\Domain\Customers\BrandMember;
use App\Domain\Loyalty\PointLedger;
use App\Domain\Loyalty\PointWallet;
use Illuminate\Support\Facades\DB;

/**
 * Reconstruye la billetera a partir del ledger: el saldo es la suma de los
 * saldos de los lotes (lot_remaining), que ya refleja vencimientos y canjes.
 */
final class ReconcileWallet
{
    /**
     * @return array{expected:int,before:int,after:int,fixed:bool}
     */
    public function handle(BrandMember $member, bool $fix = false): array
    {
        $expected = (int) PointLedger::withoutGlobalScopes()
            ->where('brand_member_id', $member->getKey())
            ->whereNotNull('lot_remaining')
            ->sum('lot_remaining');

        $wallet = PointWallet::query()->firstOrCreate(
            ['brand_member_id' => $member->getKey()],
            ['balance' => 0],
        );

        $before = (int) $wallet->balance;
        $fixed = false;
        $after = $before;

        if ($fix && $before !== $expected) {
            DB::transaction(function () use ($wallet, $expected): void {
                PointWallet::query()
                    ->whereKey($wallet->getKey())
                    ->lockForUpdate()
                    ->first();

                $wallet->balance = $expected;
                $wallet->save();
            });

            $fixed = true;
            $after = $expected;
        }

        return [
            'expected' => $expected,
            'before' => $before,
            'after' => $after,
            'fixed' => $fixed,
        ];
    }
}
