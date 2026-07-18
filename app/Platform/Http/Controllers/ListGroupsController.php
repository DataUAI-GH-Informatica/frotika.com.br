<?php

declare(strict_types=1);

namespace App\Platform\Http\Controllers;

use App\Domain\Billing\Enums\GroupLicenseStatus;
use App\Domain\Billing\Models\GroupLicense;
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
            ->with(['owner:id,name,email', 'license'])
            ->orderBy('name')
            ->get();

        $rows = $groups->map(static function (Group $group): array {
            /** @var GroupLicense|null $license */
            $license = $group->license;
            $status = $license?->status;
            $isActive = $status === GroupLicenseStatus::Active;
            $isPending = in_array($status, [
                GroupLicenseStatus::PendingPayment,
                GroupLicenseStatus::Suspended,
            ], true);

            return [
                'group' => $group,
                'companies_count' => (int) $group->getAttribute('companies_count'),
                'status_label' => $status?->label() ?? 'Sem licença',
                'status_value' => $status?->value,
                'is_pending' => $isPending,
                'mrr_cents' => $license !== null && $isActive ? (int) $license->monthly_price_cents : 0,
            ];
        });

        return view('platform.groups.index', [
            'rows' => $rows,
            'totalGroups' => $groups->count(),
            'totalMrrCents' => (int) $rows->sum('mrr_cents'),
            'totalPending' => (int) $rows->where('is_pending', true)->count(),
        ]);
    }
}
