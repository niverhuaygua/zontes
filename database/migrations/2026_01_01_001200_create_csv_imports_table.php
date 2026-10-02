<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cargas de archivos CSV: flujo subir -> vista previa -> confirmar -> procesar.
     */
    public function up(): void
    {
        Schema::create('csv_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('filename');
            $table->string('file_path');
            $table->string('status', 16)->default('pending');
            $table->json('column_map')->nullable();
            $table->string('delimiter', 4)->nullable();
            $table->json('preview')->nullable();
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_ok')->default(0);
            $table->unsignedInteger('rows_failed')->default(0);
            $table->string('error_message', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'type']);
            $table->index(['status']);
        });

        Schema::create('csv_import_errors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('csv_import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('ci', 32)->nullable();
            $table->string('message', 255);
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['csv_import_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('csv_import_errors');
        Schema::dropIfExists('csv_imports');
    }
};
