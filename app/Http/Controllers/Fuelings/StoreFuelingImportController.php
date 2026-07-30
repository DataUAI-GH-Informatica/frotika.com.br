<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fuelings;

use App\Domain\Fuelings\Enums\FuelingImportBatchStatus;
use App\Domain\Fuelings\Import\Exceptions\InvalidFuelingSheetException;
use App\Domain\Fuelings\Import\FuelingSheetReader;
use App\Domain\Fuelings\Jobs\ImportFuelingSheetJob;
use App\Domain\Fuelings\Models\FuelingImportBatch;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Http\Requests\Fuelings\BulkImportFuelingRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Recebe a planilha, confere o formato na hora e enfileira o processamento.
 *
 * O arquivo é lido duas vezes de propósito: aqui, para devolver erro de cabeçalho
 * ou de limite no próprio formulário, e no job, para importar. Descobrir que a
 * planilha estava errada só na tela de resultado seria pior.
 */
final class StoreFuelingImportController
{
    public function __invoke(BulkImportFuelingRequest $request, FuelingSheetReader $reader): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $company = Company::query()->find($user->current_company_id);

        if (! $company instanceof Company) {
            return redirect()
                ->route('companies.index')
                ->with('warning', 'Selecione uma empresa ativa antes de importar abastecimentos.');
        }

        $file = $request->file('sheet');

        if (! $file instanceof UploadedFile) {
            abort(422);
        }

        try {
            $rows = $reader->read((string) $file->getRealPath());
        } catch (InvalidFuelingSheetException $exception) {
            throw ValidationException::withMessages(['sheet' => $exception->getMessage()]);
        }

        $disk = (string) config('fuelings.import_storage_disk', 'local');
        $group = Group::query()->find($company->getAttribute('group_id'));
        $groupUuid = (string) ($group?->getAttribute('uuid') ?? 'sem-grupo');
        $batchUuid = (string) Str::uuid();

        $path = sprintf('grupos/%s/abastecimentos-import/%s/planilha.xlsx', $groupUuid, $batchUuid);
        Storage::disk($disk)->put($path, (string) file_get_contents((string) $file->getRealPath()));

        /** @var FuelingImportBatch $batch */
        $batch = FuelingImportBatch::query()->create([
            'uuid' => $batchUuid,
            'imported_by' => $user->getKey(),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 180),
            'total_rows' => count($rows),
            'status' => FuelingImportBatchStatus::Processing,
            'results' => [],
        ]);

        ImportFuelingSheetJob::dispatch(
            (int) $company->getKey(),
            (int) $user->getKey(),
            (int) $batch->getKey(),
            $disk,
            $path,
        );

        return redirect()
            ->route('fuelings.import.result', ['batch' => $batchUuid])
            ->with('status', sprintf(
                '%s enviado%s para importação. Estamos processando em segundo plano — você será avisado ao concluir.',
                count($rows) === 1 ? '1 abastecimento' : count($rows).' abastecimentos',
                count($rows) === 1 ? '' : 's',
            ));
    }
}
