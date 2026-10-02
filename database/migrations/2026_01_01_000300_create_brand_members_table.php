<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perfil de un usuario dentro de una marca. Es el dueno de la billetera.
     */
    public function up(): void
    {
        Schema::create('brand_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('active');
            $table->string('referral_code', 16)->unique();
            $table->foreignId('referred_by_member_id')->nullable();
            $table->dateTime('joined_at')->nullable();
            $table->dateTime('suspended_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'brand_id']);
            $table->index(['brand_id', 'status']);
        });

        Schema::table('brand_members', function (Blueprint $table): void {
            $table->foreign('referred_by_member_id')
                ->references('id')->on('brand_members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('brand_members', function (Blueprint $table): void {
            $table->dropForeign(['referred_by_member_id']);
        });

        Schema::dropIfExists('brand_members');
    }
};
