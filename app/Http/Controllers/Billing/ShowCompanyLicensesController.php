<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\Models\CompanyLicense;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ShowCompanyLicensesController
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if (! $user instanceof User || $user->current_group_id === null) {
            abort(403);
        }

        $group = Group::query()->findOrFail($user->current_group_id);

        if (! $user->groups()->whereKey($group->getKey())->exists()) {
            abort(403);
        }

        $licenses = CompanyLicense::query()
            ->where('group_id', $group->getKey())
            ->with([
                'company:id,trade_name',
                'latestInvoice',
            ])
            ->orderByDesc('is_primary')
            ->orderBy('company_id')
            ->get();

        $currentLicense = $licenses->firstWhere('company_id', $user->current_company_id);

        return view('billing.licenses', [
            'licenses' => $licenses,
            'currentLicense' => $currentLicense,
            'defaultMonthlyPriceCents' => (int) config('billing.company_license_monthly_price_cents', 9900),
            'canManageCompanyLicenses' => Gate::forUser($user)->allows('manage-company-licenses', $group),
        ]);
    }
}
