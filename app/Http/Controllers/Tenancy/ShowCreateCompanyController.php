<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Domain\Tenancy\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

final class ShowCreateCompanyController
{
    public function __invoke(): View
    {
        Gate::authorize('create', Company::class);

        return view('companies.create');
    }
}
