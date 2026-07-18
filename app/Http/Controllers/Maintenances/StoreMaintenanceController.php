<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenances;

use App\Domain\Maintenances\Actions\CreateMaintenance;
use App\Domain\Maintenances\Data\MaintenanceData;
use App\Domain\Tenancy\Models\Company;
use App\Http\Requests\Maintenances\StoreMaintenanceRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class StoreMaintenanceController
{
    public function __invoke(StoreMaintenanceRequest $request, CreateMaintenance $action): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $company = Company::query()->find($user->current_company_id);

        if (! $company instanceof Company) {
            return redirect()
                ->route('companies.index')
                ->with('warning', 'Selecione uma empresa ativa antes de lançar manutenções.');
        }

        $maintenance = $action->execute($user, $company, MaintenanceData::fromArray($request->validated()));

        return redirect()
            ->route('maintenances.show', ['maintenance' => $maintenance->getKey()])
            ->with('status', 'Manutenção lançada com sucesso.');
    }
}
