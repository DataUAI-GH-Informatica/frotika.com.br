<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fuelings;

use App\Domain\Fuelings\Actions\UpdateFueling;
use App\Domain\Fuelings\Data\FuelingData;
use App\Domain\Fuelings\Models\Fueling;
use App\Domain\Tenancy\Models\Company;
use App\Http\Requests\Fuelings\UpdateFuelingRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class UpdateFuelingController
{
    public function __invoke(UpdateFuelingRequest $request, int $fueling, UpdateFueling $action): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $company = Company::query()->find($user->current_company_id);

        if (! $company instanceof Company) {
            return redirect()
                ->route('companies.index')
                ->with('warning', 'Selecione uma empresa ativa.');
        }

        $model = Fueling::query()->findOrFail($fueling);

        $action->execute($user, $company, $model, FuelingData::fromArray($request->validated()));

        return redirect()
            ->route('fuelings.show', ['fueling' => $model->getKey()])
            ->with('status', 'Abastecimento atualizado com sucesso.');
    }
}
