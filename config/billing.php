<?php

declare(strict_types=1);

return [
    'company_license_trial_days' => (int) env('BILLING_COMPANY_LICENSE_TRIAL_DAYS', 7),
    'company_license_monthly_price_cents' => (int) env('BILLING_COMPANY_LICENSE_MONTHLY_PRICE_CENTS', 9900),
];
