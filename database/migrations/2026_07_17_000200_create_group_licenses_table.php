<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_licenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('trialing');
            $table->timestamp('trial_starts_at')->useCurrent();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->bigInteger('monthly_price_cents')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('group_id');
            $table->index(['group_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_licenses');
    }
};
