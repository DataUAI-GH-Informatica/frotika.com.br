<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Jobs;

use App\Domain\Fuelings\Actions\ImportFuelingRow;
use App\Domain\Fuelings\Data\FuelingImportRowResult;
use App\Domain\Fuelings\Enums\FuelingImportBatchStatus;
use App\Domain\Fuelings\Enums\FuelingImportItemStatus;
use App\Domain\Fuelings\Events\FuelingBulkImportCompleted;
use App\Domain\Fuelings\Import\Exceptions\FuelingImportRowException;
use App\Domain\Fuelings\Import\Exceptions\InvalidFuelingSheetException;
use App\Domain\Fuelings\Import\FuelingImportRow;
use App\Domain\Fuelings\Import\FuelingSheetReader;
use App\Domain\Fuelings\Models\FuelingImportBatch;
use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Importa a planilha inteira de um lote. Um job por arquivo, e não um por linha:
 * as linhas do mesmo veículo têm que ser processadas em ordem, porque o odômetro
 * de uma valida a seguinte e o km/l sai do intervalo entre abastecimentos.
 *
 * Linha com problema nunca derruba o lote — vira um item "Falhou" no resultado e
 * o job segue para a próxima.
 *
 * Regra de tenancy (AGENTS.md #5): recebe company_id explícito e abre o contexto
 * com TenantContext::runFor(). A planilha não vai para a fila, só o caminho no
 * storage privado do grupo.
 */
final class ImportFuelingSheetJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 900;

    /**
     * De quantas em quantas linhas o progresso é gravado. Gravar a cada linha
     * reescreveria o json de resultados mil vezes; esperar o fim de tudo deixaria
     * a tela de acompanhamento parada em zero.
     */
    private const FLUSH_EVERY = 20;

    public function __construct(
        public readonly int $companyId,
        public readonly int $userId,
        public readonly int $batchId,
        public readonly string $storageDisk,
        public readonly string $storagePath,
    ) {}

    public function handle(TenantContext $tenant, FuelingSheetReader $reader, ImportFuelingRow $import): void
    {
        $company = Company::query()->find($this->companyId);

        if (! $company instanceof Company) {
            return;
        }

        $tenant->runFor($company, function () use ($company, $reader, $import): void {
            $this->process($company, $reader, $import);
        });
    }

    private function process(Company $company, FuelingSheetReader $reader, ImportFuelingRow $import): void
    {
        $user = User::query()->find($this->userId);
        $disk = Storage::disk($this->storageDisk);

        try {
            if (! $user instanceof User || ! $disk->exists($this->storagePath)) {
                throw InvalidFuelingSheetException::unreadable();
            }

            $rows = $this->readRows($reader, (string) $disk->get($this->storagePath));
        } catch (InvalidFuelingSheetException $exception) {
            // O upload já validou o arquivo; chegar aqui é o arquivo ter sumido
            // ou corrompido entre o envio e o processamento.
            $this->flush([FuelingImportRowResult::failed(0, $exception->getMessage())]);
            $this->complete();
            $this->notify();

            return;
        } finally {
            $disk->delete($this->storagePath);
        }

        $this->syncTotal(count($rows));
        $this->importRows($user, $company, $import, $rows);

        $this->complete();
        $this->notify();
    }

    /**
     * @param  list<FuelingImportRow>  $rows
     */
    private function importRows(User $user, Company $company, ImportFuelingRow $import, array $rows): void
    {
        $pending = [];

        foreach ($rows as $row) {
            try {
                $pending[] = $import->execute($user, $company, $row);
            } catch (FuelingImportRowException $exception) {
                $pending[] = FuelingImportRowResult::failed($row->number, $exception->getMessage());
            } catch (Throwable $exception) {
                report($exception);
                $pending[] = FuelingImportRowResult::failed(
                    $row->number,
                    'Erro inesperado ao importar esta linha. A equipe foi notificada.',
                );
            }

            if (count($pending) >= self::FLUSH_EVERY) {
                $this->flush($pending);
                $pending = [];
            }
        }

        if ($pending !== []) {
            $this->flush($pending);
        }
    }

    /**
     * O leitor trabalha com caminho de arquivo e o disco pode não ser local.
     *
     * @return list<FuelingImportRow>
     */
    private function readRows(FuelingSheetReader $reader, string $contents): array
    {
        $temporary = (string) tempnam(sys_get_temp_dir(), 'frotika-fueling-import');

        try {
            file_put_contents($temporary, $contents);

            return $reader->read($temporary);
        } finally {
            unlink($temporary);
        }
    }

    /**
     * O total gravado no upload é a contagem daquela leitura. Se a releitura do
     * job divergir, quem manda é ela — a tela mostra o que realmente existe.
     */
    private function syncTotal(int $total): void
    {
        FuelingImportBatch::query()
            ->whereKey($this->batchId)
            ->where('total_rows', '!=', $total)
            ->update(['total_rows' => $total]);
    }

    /**
     * @param  list<FuelingImportRowResult>  $results
     */
    private function flush(array $results): void
    {
        DB::transaction(function () use ($results): void {
            $batch = FuelingImportBatch::query()->lockForUpdate()->find($this->batchId);

            if (! $batch instanceof FuelingImportBatch) {
                return;
            }

            $stored = $batch->results ?? [];

            foreach ($results as $result) {
                $stored[] = $result->toArray();

                if ($result->status === FuelingImportItemStatus::Imported) {
                    $batch->imported_count = $batch->imported_count + 1;
                } elseif ($result->status === FuelingImportItemStatus::Ignored) {
                    $batch->ignored_count = $batch->ignored_count + 1;
                } else {
                    $batch->failed_count = $batch->failed_count + 1;
                }
            }

            $batch->results = $stored;
            $batch->processed_rows = $batch->processed_rows + count($results);
            $batch->save();
        });
    }

    private function complete(): void
    {
        FuelingImportBatch::query()
            ->whereKey($this->batchId)
            ->update(['status' => FuelingImportBatchStatus::Completed->value]);
    }

    private function notify(): void
    {
        $batch = FuelingImportBatch::query()->find($this->batchId);

        if (! $batch instanceof FuelingImportBatch) {
            return;
        }

        FuelingBulkImportCompleted::dispatch(
            $this->userId,
            (string) $batch->getAttribute('uuid'),
            $batch->total_rows,
            $batch->imported_count,
            $batch->ignored_count,
            $batch->failed_count,
        );
    }
}
