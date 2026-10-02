<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clientes precargados por CSV que aun no tienen cuenta en el portal.
     */
    public function up(): void
    {
        Schema::create('customer_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('ci', 255);
            $table->char('ci_hash', 64);
            $table->string('first_name', 80)->nullable();
            $table->string('last_name', 80)->nullable();
            $table->text('email')->nullable();
            $table->text('phone')->nullable();
            $table->dateTime('registered_at')->nullable();
            $table->string('claim_status', 32)->default('unclaimed');
            $table->foreignId('claimed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('claimed_at')->nullable();
            $table->foreignId('source_import_id')->nullable();
            $table->timestamps();

            $table->unique(['brand_id', 'ci']);
            $table->unique(['brand_id', 'ci_hash']);
            $table->index(['brand_id', 'claim_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_records');
    }
};
