<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ShowCompanyController
{
    public function __invoke(Request $request, Company $company): View
    {
        Gate::authorize('view', $company);

        $company->loadCount('users');

        $user = $request->user();
        $group = $company->group()->first();

        return view('companies.show', [
            'company' => $company,
            'canManage' => Gate::allows('update', $company),
            'isCurrent' => $user instanceof User && (int) $user->current_company_id === $company->getKey(),
            'isPrimary' => $group !== null && (int) $group->primary_company_id === $company->getKey(),
        ]);
    }
}
