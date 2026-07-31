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
        Schema::table('financial_entries', function (Blueprint $table): void {
            $table->date('reference_date')->nullable()->after('competence_date');
            $table->unsignedInteger('installment_number')->nullable()->after('recurrence_id');
            $table->unsignedInteger('installment_total')->nullable()->after('installment_number');
            $table->bigInteger('settlement_discount_cents')->default(0)->after('amount_cents');
            $table->bigInteger('settlement_interest_cents')->default(0)->after('settlement_discount_cents');

            $table->index(['company_id', 'reference_date']);
            $table->index(['company_id', 'recurrence_id', 'reference_date']);
        });

        DB::table('financial_entries')
            ->whereNull('reference_date')
            ->update(['reference_date' => DB::raw('competence_date')]);
    }

    public function down(): void
    {
        Schema::table('financial_entries', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'reference_date']);
            $table->dropIndex(['company_id', 'recurrence_id', 'reference_date']);

            $table->dropColumn('settlement_interest_cents');
            $table->dropColumn('settlement_discount_cents');
            $table->dropColumn('installment_total');
            $table->dropColumn('installment_number');
            $table->dropColumn('reference_date');
        });
    }
};
