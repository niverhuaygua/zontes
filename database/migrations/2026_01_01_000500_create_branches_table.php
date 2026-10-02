<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sucursales por marca y personal autorizado a validar cupones.
     */
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('city', 80)->nullable();
            $table->string('address', 180)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->index(['brand_id', 'active']);
        });

        Schema::create('branch_staff', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_staff');
        Schema::dropIfExists('branches');
    }
};
