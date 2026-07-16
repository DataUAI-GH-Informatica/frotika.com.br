<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('financial_categories')->nullOnDelete();
            $table->string('code', 20);
            $table->string('name', 120);
            $table->string('type', 20)->nullable();
            $table->string('dre_group', 30)->nullable();
            $table->string('allocation', 20)->nullable();
            $table->boolean('affects_cashflow')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'active']);
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'dre_group']);
        });

        DB::statement('CREATE UNIQUE INDEX financial_categories_company_code_unique ON financial_categories (company_id, code) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_categories');
    }
};
