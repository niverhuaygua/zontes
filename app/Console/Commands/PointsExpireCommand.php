<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Loyalty\Actions\ExpirePoints;
use Illuminate\Console\Command;

class PointsExpireCommand extends Command
{
    protected $signature = 'points:expire';

    protected $description = 'Genera los movimientos de vencimiento de los lotes de puntos caducados.';

    public function handle(ExpirePoints $expirePoints): int
    {
        $count = $expirePoints->handle();

        $this->info("Lotes vencidos procesados: {$count}");

        return self::SUCCESS;
    }
}
