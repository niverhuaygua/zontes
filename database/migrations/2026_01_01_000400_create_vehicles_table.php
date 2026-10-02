<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_member_id')->constrained()->cascadeOnDelete();
            $table->string('vin', 32)->nullable()->index();
            $table->string('model', 80)->nullable();
            $table->date('purchase_date')->nullable();
            $table->timestamps();

            $table->index(['brand_member_id', 'vin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
