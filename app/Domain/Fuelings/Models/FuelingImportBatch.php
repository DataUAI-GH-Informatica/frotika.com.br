<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Models;

use App\Domain\Fuelings\Enums\FuelingImportBatchStatus;
use App\Models\User;
use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lote de importação de abastecimentos por planilha. Guarda os contadores e o
 * resultado linha a linha para a tela de acompanhamento.
 *
 * @property FuelingImportBatchStatus $status
 * @property list<array<string, mixed>>|null $results
 * @property int $total_rows
 * @property int $processed_rows
 * @property int $imported_count
 * @property int $ignored_count
 * @property int $failed_count
 */
final class FuelingImportBatch extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    /**
     * @return BelongsTo<User, $this>
     */
    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === FuelingImportBatchStatus::Completed;
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => FuelingImportBatchStatus::class,
            'results' => 'array',
            'total_rows' => 'integer',
            'processed_rows' => 'integer',
            'imported_count' => 'integer',
            'ignored_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }
}
