<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Customers\BrandMember;
use App\Domain\Loyalty\Actions\ReconcileWallet;
use Illuminate\Console\Command;

class PointsReconcileCommand extends Command
{
    protected $signature = 'points:reconcile {--fix : Corrige la billetera cuando no coincide con el ledger} {--brand= : Solo las membresias de esta marca (slug)}';

    protected $description = 'Compara el saldo de cada billetera con la suma de lotes del ledger.';

    public function handle(ReconcileWallet $reconcile): int
    {
        $query = BrandMember::query()->with('brand');

        if ($brand = $this->option('brand')) {
            $query->whereHas('brand', fn ($q) => $q->where('slug', $brand));
        }

        $fix = (bool) $this->option('fix');
        $mismatches = 0;
        $checked = 0;

        $query->chunkById(200, function ($members) use ($reconcile, $fix, &$mismatches, &$checked): void {
            foreach ($members as $member) {
                $checked++;
                $result = $reconcile->handle($member, $fix);

                if (! $result['fixed']) {
                    continue;
                }

                $mismatches++;
                $this->warn(sprintf(
                    'Miembro #%d (%s): billetera %d, ledger %d%s',
                    $member->getKey(),
                    $member->brand->name,
                    $result['before'],
                    $result['expected'],
                    $fix ? ' -> corregido' : '',
                ));
            }
        });

        $this->info("Membresias revisadas: {$checked}. Diferencias: {$mismatches}.");

        return $mismatches === 0 || $fix ? self::SUCCESS : self::FAILURE;
    }
}
