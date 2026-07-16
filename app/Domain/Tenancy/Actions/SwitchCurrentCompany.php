<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

final class SwitchCurrentCompany
{
    public function execute(User $user, int $companyId): Company
    {
        $company = Company::query()->find($companyId);

        if ($company === null) {
            throw (new ModelNotFoundException)->setModel(Company::class, [$companyId]);
        }

        Gate::forUser($user)->authorize('switch-company', $company);

        $user->forceFill([
            'current_group_id' => $company->getAttribute('group_id'),
            'current_company_id' => $company->getKey(),
        ])->save();

        return $company;
    }
}
