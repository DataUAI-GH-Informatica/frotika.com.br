<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Domain\Tenancy\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class ShowEditCompanyController
{
    public function __invoke(Company $company): View
    {
        Gate::authorize('update', $company);

        return view('companies.edit', [
            'company' => $company,
        ]);
    }
}
