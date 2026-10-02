<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cache del saldo por membresia. Se actualiza en la misma transaccion que
     * el movimiento del ledger y puede reconstruirse con points:reconcile.
     */
    public function up(): void
    {
        Schema::create('point_wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_member_id')->unique()->constrained()->cascadeOnDelete();
            $table->bigInteger('balance')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_wallets');
    }
};
