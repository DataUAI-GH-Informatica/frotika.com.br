<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fuelings;

use App\Domain\Attachments\Actions\StoreAttachment;
use App\Domain\Fuelings\Actions\CreateFueling;
use App\Domain\Fuelings\Data\FuelingData;
use App\Domain\Tenancy\Models\Company;
use App\Http\Controllers\Concerns\HandlesAttachmentUploads;
use App\Http\Requests\Fuelings\StoreFuelingRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class StoreFuelingController
{
    use HandlesAttachmentUploads;

    public function __invoke(
        StoreFuelingRequest $request,
        CreateFueling $action,
        StoreAttachment $storeAttachment,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $company = Company::query()->find($user->current_company_id);

        if (! $company instanceof Company) {
            return redirect()
                ->route('companies.index')
                ->with('warning', 'Selecione uma empresa ativa antes de lançar abastecimentos.');
        }

        $fueling = $action->execute($user, $company, FuelingData::fromArray($request->validated()));

        $this->attachUploadedFiles($request, $storeAttachment, $user, $company, $fueling);

        return redirect()
            ->route('fuelings.show', ['fueling' => $fueling->getKey()])
            ->with('status', 'Abastecimento lançado com sucesso.');
    }
}
