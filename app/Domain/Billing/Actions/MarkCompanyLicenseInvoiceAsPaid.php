<?php

declare(strict_types=1);

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Enums\CompanyLicenseInvoiceStatus;
use App\Domain\Billing\Enums\CompanyLicenseStatus;
use App\Domain\Billing\Models\CompanyLicenseInvoice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class MarkCompanyLicenseInvoiceAsPaid
{
    public function execute(User $actor, CompanyLicenseInvoice $invoice, ?CarbonImmutable $paidAt, ?string $note): CompanyLicenseInvoice
    {
        $group = $invoice->group()->firstOrFail();
        Gate::forUser($actor)->authorize('manage-company-licenses', $group);

        /** @var CompanyLicenseInvoice $updated */
        $updated = DB::transaction(function () use ($actor, $invoice, $paidAt, $note): CompanyLicenseInvoice {
            $effectivePaidAt = $paidAt ?? CarbonImmutable::now();

            $invoice->forceFill([
                'status' => CompanyLicenseInvoiceStatus::Paid,
                'paid_at' => $effectivePaidAt,
                'paid_note' => $note,
                'confirmed_by_user_id' => $actor->getKey(),
            ])->save();

            $license = $invoice->license()->firstOrFail();
            $license->forceFill([
                'status' => CompanyLicenseStatus::Active,
                'activated_at' => $license->activated_at ?? $effectivePaidAt,
                'suspended_at' => null,
            ])->save();

            return $invoice->fresh();
        });

        return $updated;
    }
}
