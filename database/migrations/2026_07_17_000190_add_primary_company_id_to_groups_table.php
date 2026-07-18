<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            $table->foreignId('primary_company_id')
                ->nullable()
                ->after('owner_user_id')
                ->constrained('companies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('primary_company_id');
        });
    }
};
