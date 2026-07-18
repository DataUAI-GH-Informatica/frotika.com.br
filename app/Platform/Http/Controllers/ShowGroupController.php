<?php

declare(strict_types=1);

namespace App\Platform\Http\Controllers;

use App\Domain\Billing\Models\GroupLicense;
use App\Domain\Tenancy\Models\Group;
use Illuminate\Contracts\View\View;

final class ShowGroupController
{
    public function __invoke(Group $group): View
    {
        $group->load(['owner:id,name,email']);

        $license = GroupLicense::query()
            ->where('group_id', $group->getKey())
            ->with('latestInvoice')
            ->first();

        $companies = $group->companies()
            ->orderByDesc('id')
            ->get(['id', 'group_id', 'trade_name', 'legal_name', 'cnpj', 'city', 'state']);

        $users = $group->users()
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email']);

        return view('platform.groups.show', [
            'group' => $group,
            'license' => $license,
            'companies' => $companies,
            'users' => $users,
            'defaultMonthlyPriceCents' => (int) config('billing.group_license_monthly_price_cents', 9900),
        ]);
    }
}
