<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Domain\Tenancy\Models\Company;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ListCompaniesController
{
    public function __invoke(Request $request): View
    {
        Gate::authorize('viewAny', Company::class);

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $companies = Company::query()
            ->where('group_id', $user->current_group_id)
            ->orderByDesc('id')
            ->get(['id', 'group_id', 'trade_name', 'legal_name', 'cnpj', 'city', 'state']);

        return view('companies.index', [
            'companies' => $companies,
            'canManage' => Gate::allows('create', Company::class),
            'currentCompanyId' => $user->current_company_id,
        ]);
    }
}
