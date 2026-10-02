<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eventos de negocio (compras, mantenimientos) cargados por CSV para un
     * cliente que todavia no tiene cuenta. Se acreditan al reclamar la cuenta.
     */
    public function up(): void
    {
        Schema::create('pending_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_record_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->dateTime('occurred_at');
            $table->string('external_id')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('payload')->nullable();
            $table->string('status', 16)->default('pending');
            $table->foreignId('credited_member_id')->nullable()->constrained('brand_members')->nullOnDelete();
            $table->foreignId('source_import_id')->nullable();
            $table->dateTime('credited_at')->nullable();
            $table->timestamps();

            $table->unique(['brand_id', 'event_type', 'external_id'], 'pending_events_external_unique');
            $table->index(['brand_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_events');
    }
};
