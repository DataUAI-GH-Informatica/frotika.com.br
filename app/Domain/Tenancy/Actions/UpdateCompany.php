<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Data\CompanyData;
use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class UpdateCompany
{
    public function execute(User $actor, Company $company, CompanyData $data): Company
    {
        Gate::forUser($actor)->authorize('update', $company);

        $company->forceFill($data->toAttributes())->save();

        return $company->refresh();
    }
}
