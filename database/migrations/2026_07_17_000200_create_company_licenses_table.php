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
        Schema::create('company_licenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->string('status', 20)->default('trialing');
            $table->timestamp('trial_starts_at')->useCurrent();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->bigInteger('monthly_price_cents')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('company_id');
            $table->index(['company_id', 'status']);
            $table->index(['group_id', 'status']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE company_licenses
                ADD COLUMN primary_flag TINYINT
                    GENERATED ALWAYS AS (
                        CASE WHEN is_primary = 1 AND deleted_at IS NULL THEN 1 ELSE NULL END
                    ) VIRTUAL,
                ADD UNIQUE KEY uq_company_licenses_primary_per_group (group_id, primary_flag)');

            return;
        }

        DB::statement('CREATE UNIQUE INDEX uq_company_licenses_primary_per_group
            ON company_licenses (group_id)
            WHERE is_primary = 1 AND deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('company_licenses');
    }
};
