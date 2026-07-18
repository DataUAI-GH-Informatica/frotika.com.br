<?php

declare(strict_types=1);

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Enums\CompanyLicenseStatus;
use App\Domain\Billing\Models\CompanyLicense;
use App\Domain\Tenancy\Models\Company;

final class RegisterCompanyLicense
{
    public function execute(Company $company, bool $isPrimary, int $trialDays, int $monthlyPriceCents): CompanyLicense
    {
        return CompanyLicense::query()->create([
            'group_id' => $company->getAttribute('group_id'),
            'company_id' => $company->getKey(),
            'is_primary' => $isPrimary,
            'status' => CompanyLicenseStatus::Trialing,
            'trial_starts_at' => now(),
            'trial_ends_at' => now()->addDays($trialDays),
            'monthly_price_cents' => $monthlyPriceCents,
        ]);
    }
}
