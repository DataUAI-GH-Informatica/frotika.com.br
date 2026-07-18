<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Domain\Tenancy\Actions\DeactivateCompany;
use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class DeactivateCompanyController
{
    public function __invoke(Request $request, Company $company, DeactivateCompany $action): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $action->execute($user, $company);

        return redirect()
            ->route('companies.index')
            ->with('status', 'Empresa desativada com sucesso.');
    }
}
