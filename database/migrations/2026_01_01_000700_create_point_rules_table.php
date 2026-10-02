<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reglas de acumulacion por marca y tipo de evento.
     */
    public function up(): void
    {
        Schema::create('point_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('event_type', 40);
            $table->string('calc_type', 20);
            $table->decimal('value', 12, 2)->default(0);
            $table->decimal('min_amount', 12, 2)->nullable();
            $table->json('conditions')->nullable();
            $table->unsignedSmallInteger('expires_after_days')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'event_type', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_rules');
    }
};
