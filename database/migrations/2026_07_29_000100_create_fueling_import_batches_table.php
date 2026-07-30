<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fueling_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid');
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('original_name', 180);

            // Uma linha da planilha = um abastecimento. Cada linha termina em um
            // dos três estados: importada, ignorada (duplicada) ou com falha.
            $table->unsignedSmallInteger('total_rows');
            $table->unsignedSmallInteger('processed_rows')->default(0);
            $table->unsignedSmallInteger('imported_count')->default(0);
            $table->unsignedSmallInteger('ignored_count')->default(0);
            $table->unsignedSmallInteger('failed_count')->default(0);

            $table->string('status', 20)->default('processing');
            // Resultado por linha: [{row, status, message, fueling_id, plate, code}].
            $table->json('results')->nullable();
            $table->timestamps();

            $table->unique('uuid');
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fueling_import_batches');
    }
};
