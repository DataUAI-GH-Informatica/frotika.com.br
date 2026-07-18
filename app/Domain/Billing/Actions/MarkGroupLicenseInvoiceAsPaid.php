<?php

declare(strict_types=1);

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Enums\GroupLicenseInvoiceStatus;
use App\Domain\Billing\Enums\GroupLicenseStatus;
use App\Domain\Billing\Models\GroupLicenseInvoice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class MarkGroupLicenseInvoiceAsPaid
{
    public function execute(User $actor, GroupLicenseInvoice $invoice, ?CarbonImmutable $paidAt, ?string $note): GroupLicenseInvoice
    {
        Gate::forUser($actor)->authorize('access-platform');

        /** @var GroupLicenseInvoice $updated */
        $updated = DB::transaction(function () use ($actor, $invoice, $paidAt, $note): GroupLicenseInvoice {
            $effectivePaidAt = $paidAt ?? CarbonImmutable::now();

            $invoice->forceFill([
                'status' => GroupLicenseInvoiceStatus::Paid,
                'paid_at' => $effectivePaidAt,
                'paid_note' => $note,
                'confirmed_by_user_id' => $actor->getKey(),
            ])->save();

            $license = $invoice->license()->firstOrFail();
            $license->forceFill([
                'status' => GroupLicenseStatus::Active,
                'activated_at' => $license->activated_at ?? $effectivePaidAt,
                'suspended_at' => null,
            ])->save();

            return $invoice->fresh();
        });

        return $updated;
    }
}
