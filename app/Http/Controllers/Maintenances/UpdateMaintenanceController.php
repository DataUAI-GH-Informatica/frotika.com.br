<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenances;

use App\Domain\Maintenances\Actions\UpdateMaintenance;
use App\Domain\Maintenances\Data\MaintenanceData;
use App\Domain\Maintenances\Models\Maintenance;
use App\Domain\Tenancy\Models\Company;
use App\Http\Requests\Maintenances\UpdateMaintenanceRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class UpdateMaintenanceController
{
    public function __invoke(UpdateMaintenanceRequest $request, int $maintenance, UpdateMaintenance $action): RedirectResponse
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

        $action->execute($user, $company, $model, MaintenanceData::fromArray($request->validated()));

        return redirect()
            ->route('maintenances.show', ['maintenance' => $model->getKey()])
            ->with('status', 'Manutenção atualizada com sucesso.');
    }
}
