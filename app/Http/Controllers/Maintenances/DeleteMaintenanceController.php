<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenances;

use App\Domain\Maintenances\Actions\DeleteMaintenance;
use App\Domain\Maintenances\Models\Maintenance;
use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DeleteMaintenanceController
{
    public function __invoke(Request $request, int $maintenance, DeleteMaintenance $action): RedirectResponse
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

        $model = Maintenance::query()->findOrFail($maintenance);

        $action->execute($user, $company, $model);

        return redirect()
            ->route('maintenances.index')
            ->with('status', 'Manutenção excluída.');
    }
}
