<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Observers;

use App\Domain\Billing\Enums\CompanyLicenseStatus;
use App\Domain\Billing\Models\CompanyLicense;
use App\Domain\Tenancy\Models\Company;

final class CompanyObserver
{
    public function created(Company $company): void
    {
        if (CompanyLicense::query()->where('company_id', $company->getKey())->exists()) {
            return;
        }

        $group = $company->group()->first();

        if ($group === null) {
            return;
        }

        $trialDays = (int) config('billing.company_license_trial_days', 7);
        $monthlyPriceCents = (int) config('billing.company_license_monthly_price_cents', 9900);
        $isPrimary = $group->primary_company_id === null;

        CompanyLicense::query()->create([
            'group_id' => $group->getKey(),
            'company_id' => $company->getKey(),
            'is_primary' => $isPrimary,
            'status' => CompanyLicenseStatus::Trialing,
            'trial_starts_at' => now(),
            'trial_ends_at' => now()->addDays($trialDays),
            'monthly_price_cents' => $monthlyPriceCents,
        ]);

        if ($isPrimary) {
            $group->forceFill([
                'primary_company_id' => $company->getKey(),
            ])->save();
        }
    }
}
