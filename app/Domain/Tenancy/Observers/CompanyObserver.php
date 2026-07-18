<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Observers;

use App\Domain\Tenancy\Models\Company;

final class CompanyObserver
{
    public function created(Company $company): void
    {
        $group = $company->group()->first();

        if ($group === null) {
            return;
        }

        // A licença é do grupo (criada no onboarding). A empresa apenas define
        // a empresa principal do grupo quando ainda não houver nenhuma.
        if ($group->primary_company_id === null) {
            $group->forceFill([
                'primary_company_id' => $company->getKey(),
            ])->save();
        }
    }
}
