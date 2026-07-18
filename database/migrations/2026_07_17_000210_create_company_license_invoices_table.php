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
        Schema::create('company_license_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_license_id')->constrained('company_licenses')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('reference_month');
            $table->bigInteger('amount_cents');
            $table->date('due_date');
            $table->string('status', 20)->default('pending');
            $table->string('boleto_number', 100)->nullable();
            $table->string('boleto_url')->nullable();
            $table->string('boleto_pdf_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('paid_note')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status', 'due_date']);
            $table->index(['group_id', 'status', 'due_date']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE company_license_invoices
                ADD COLUMN active_reference_month DATE
                    GENERATED ALWAYS AS (
                        CASE WHEN deleted_at IS NULL THEN reference_month ELSE NULL END
                    ) VIRTUAL,
                ADD UNIQUE KEY uq_company_license_invoices_monthly (company_id, active_reference_month)');

            return;
        }

        DB::statement('CREATE UNIQUE INDEX uq_company_license_invoices_monthly
            ON company_license_invoices (company_id, reference_month)
            WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('company_license_invoices');
    }
};
