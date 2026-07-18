<?php

declare(strict_types=1);

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Enums\GroupLicenseStatus;
use App\Domain\Billing\Models\GroupLicense;
use App\Domain\Tenancy\Models\Group;

final class RegisterGroupLicense
{
    public function execute(Group $group, int $trialDays, int $monthlyPriceCents): GroupLicense
    {
        return GroupLicense::query()->create([
            'group_id' => $group->getKey(),
            'status' => GroupLicenseStatus::Trialing,
            'trial_starts_at' => now(),
            'trial_ends_at' => now()->addDays($trialDays),
            'monthly_price_cents' => $monthlyPriceCents,
        ]);
    }
}
