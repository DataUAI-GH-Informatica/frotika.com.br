<?php

declare(strict_types=1);

return [
    'group_license_trial_days' => (int) env('BILLING_GROUP_LICENSE_TRIAL_DAYS', 7),
    'group_license_monthly_price_cents' => (int) env('BILLING_GROUP_LICENSE_MONTHLY_PRICE_CENTS', 9900),
];
