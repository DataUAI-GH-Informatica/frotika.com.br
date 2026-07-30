<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('attachable_type', 191);
            $table->unsignedBigInteger('attachable_id');
            $table->string('disk', 40);
            $table->string('path', 400);
            $table->string('original_name', 255);
            $table->string('mime', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'attachable_type', 'attachable_id'], 'attachments_owner_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
