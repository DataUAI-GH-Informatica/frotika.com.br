<?php

declare(strict_types=1);

namespace App\Platform\Http\Controllers;

use App\Domain\Billing\Enums\CompanyLicenseStatus;
use App\Domain\Tenancy\Enums\GroupType;
use App\Domain\Tenancy\Models\Group;
use Illuminate\Contracts\View\View;

final class ListGroupsController
{
    public function __invoke(): View
    {
        $groups = Group::query()
            ->where('type', GroupType::Customer->value)
            ->withCount('companies')
            ->with(['owner:id,name,email', 'companyLicenses:id,group_id,status,monthly_price_cents'])
            ->orderBy('name')
            ->get();

        $rows = $groups->map(static function (Group $group): array {
            $licenses = $group->companyLicenses;

            $activeLicenses = $licenses->where('status', CompanyLicenseStatus::Active);

            return [
                'group' => $group,
                'companies_count' => (int) $group->getAttribute('companies_count'),
                'active_count' => $activeLicenses->count(),
                'pending_count' => $licenses->whereIn('status', [
                    CompanyLicenseStatus::PendingPayment,
                    CompanyLicenseStatus::Suspended,
                ])->count(),
                'trial_count' => $licenses->where('status', CompanyLicenseStatus::Trialing)->count(),
                'mrr_cents' => (int) $activeLicenses->sum('monthly_price_cents'),
            ];
        });

        return view('platform.groups.index', [
            'rows' => $rows,
            'totalGroups' => $groups->count(),
            'totalMrrCents' => (int) $rows->sum('mrr_cents'),
            'totalPending' => (int) $rows->sum('pending_count'),
        ]);
    }
}
