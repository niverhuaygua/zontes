<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Referidos: un unico referidor por referido y marca.
     */
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referrer_member_id')->constrained('brand_members')->cascadeOnDelete();
            $table->foreignId('referred_member_id')->unique()->constrained('brand_members')->cascadeOnDelete();
            $table->string('status', 16)->default('registered');
            $table->dateTime('registered_at')->nullable();
            $table->dateTime('purchased_at')->nullable();
            $table->string('reject_reason')->nullable();
            $table->timestamps();

            $table->unique(['brand_id', 'referred_member_id']);
            $table->index(['brand_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
