<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Libro de movimientos de puntos. Es INMUTABLE: nunca se actualizan ni se
     * borran puntos de esta tabla. La unica columna mutable es lot_remaining,
     * que se decrementa al consumir un lote (FIFO por vencimiento).
     */
    public function up(): void
    {
        Schema::create('point_ledger', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_member_id')->constrained()->cascadeOnDelete();
            $table->string('type', 16);
            $table->bigInteger('points');
            $table->bigInteger('lot_remaining')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->foreignId('rule_id')->nullable()->constrained('point_rules')->nullOnDelete();
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('idempotency_key', 191)->unique();
            $table->string('note')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['brand_member_id', 'expires_at']);
            $table->index(['brand_member_id', 'type']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_ledger');
    }
};
