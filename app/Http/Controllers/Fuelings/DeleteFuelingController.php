<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fuelings;

use App\Domain\Fuelings\Actions\DeleteFueling;
use App\Domain\Fuelings\Models\Fueling;
use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DeleteFuelingController
{
    public function __invoke(Request $request, int $fueling, DeleteFueling $action): RedirectResponse
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

        $action->execute($user, $company, $model);

        return redirect()
            ->route('fuelings.index')
            ->with('status', 'Abastecimento excluído.');
    }
}
