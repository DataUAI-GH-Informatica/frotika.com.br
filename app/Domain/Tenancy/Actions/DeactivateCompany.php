<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class DeactivateCompany
{
    public function execute(User $actor, Company $company): void
    {
        Gate::forUser($actor)->authorize('delete', $company);

        /** @var Group $group */
        $group = $company->group()->firstOrFail();

        if ((int) $group->primary_company_id === $company->getKey()) {
            throw ValidationException::withMessages([
                'company' => 'Não é possível desativar a empresa principal do grupo.',
            ]);
        }

        if ((int) $actor->current_company_id === $company->getKey()) {
            throw ValidationException::withMessages([
                'company' => 'Troque a empresa ativa antes de desativar esta empresa.',
            ]);
        }

        $company->delete();
    }
}
