<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recurrences', function (Blueprint $table): void {
            $table->string('kind', 30)->default('recurring')->after('frequency');
            $table->date('fixed_competence_date')->nullable()->after('ends_at');

            $table->index(['company_id', 'active', 'kind']);
            $table->index(['company_id', 'fixed_competence_date']);
        });
    }

    public function down(): void
    {
        Schema::table('recurrences', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'active', 'kind']);
            $table->dropIndex(['company_id', 'fixed_competence_date']);

            $table->dropColumn('fixed_competence_date');
            $table->dropColumn('kind');
        });
    }
};
