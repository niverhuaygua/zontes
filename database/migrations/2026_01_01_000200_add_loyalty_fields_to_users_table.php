<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Identidad global del usuario: CI, contacto y rol de administracion.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('ci', 255)->nullable()->after('name');
            $table->char('ci_hash', 64)->nullable()->unique()->after('ci');
            $table->text('phone')->nullable()->after('ci_hash');
            $table->text('birth_date')->nullable()->after('phone');
            $table->string('role', 32)->default('customer')->after('birth_date')->index();
            $table->timestamp('last_login_at')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['ci_hash']);
            $table->dropColumn(['ci', 'ci_hash', 'phone', 'birth_date', 'role', 'last_login_at']);
        });
    }
};
