<?php

declare(strict_types=1);

namespace App\Platform\Http\Controllers;

use App\Domain\Billing\Models\CompanyLicense;
use App\Domain\Tenancy\Models\Group;
use Illuminate\Contracts\View\View;

final class ShowGroupController
{
    public function __invoke(Group $group): View
    {
        $group->load(['owner:id,name,email']);

        $licenses = CompanyLicense::query()
            ->where('group_id', $group->getKey())
            ->with(['company:id,trade_name,cnpj', 'latestInvoice'])
            ->orderByDesc('is_primary')
            ->orderBy('company_id')
            ->get();

        $users = $group->users()
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email']);

        return view('platform.groups.show', [
            'group' => $group,
            'licenses' => $licenses,
            'users' => $users,
            'defaultMonthlyPriceCents' => (int) config('billing.company_license_monthly_price_cents', 9900),
        ]);
    }
}
