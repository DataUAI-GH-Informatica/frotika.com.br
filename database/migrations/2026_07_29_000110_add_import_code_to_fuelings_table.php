<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chave externa opcional da planilha de importação. É o identificador que o
     * cliente já usa no controle dele (cupom do cartão de frota, id do sistema
     * do posto). Quando vem preenchido, o índice único garante idempotência no
     * banco, não só na aplicação: duas importações simultâneas da mesma
     * planilha não conseguem gravar o mesmo código duas vezes.
     */
    public function up(): void
    {
        Schema::table('fuelings', function (Blueprint $table): void {
            $table->string('import_code', 60)->nullable()->after('invoice_number');

            $table->unique(['company_id', 'import_code']);
        });
    }

    public function down(): void
    {
        Schema::table('fuelings', function (Blueprint $table): void {
            $table->dropUnique(['company_id', 'import_code']);
            $table->dropColumn('import_code');
        });
    }
};
