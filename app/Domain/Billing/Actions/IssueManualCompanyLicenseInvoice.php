<?php

declare(strict_types=1);

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Data\IssueManualCompanyLicenseInvoiceData;
use App\Domain\Billing\Enums\CompanyLicenseInvoiceStatus;
use App\Domain\Billing\Enums\CompanyLicenseStatus;
use App\Domain\Billing\Models\CompanyLicense;
use App\Domain\Billing\Models\CompanyLicenseInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class IssueManualCompanyLicenseInvoice
{
    public function execute(User $actor, CompanyLicense $license, IssueManualCompanyLicenseInvoiceData $data): CompanyLicenseInvoice
    {
        $group = $license->group()->firstOrFail();
        Gate::forUser($actor)->authorize('manage-company-licenses', $group);

        if ($license->trial_ends_at !== null && now()->lt($license->trial_ends_at)) {
            throw ValidationException::withMessages([
                'due_date' => 'A licença ainda está em trial. Emita o boleto após o fim dos 7 dias.',
            ]);
        }

        $hasOpenInvoice = $license->invoices()
            ->whereIn('status', [
                CompanyLicenseInvoiceStatus::Pending->value,
                CompanyLicenseInvoiceStatus::Overdue->value,
            ])
            ->exists();

        if ($hasOpenInvoice) {
            throw ValidationException::withMessages([
                'company_license_id' => 'Já existe boleto pendente para esta empresa.',
            ]);
        }

        /** @var CompanyLicenseInvoice $invoice */
        $invoice = DB::transaction(function () use ($actor, $license, $data): CompanyLicenseInvoice {
            $invoice = CompanyLicenseInvoice::query()->create([
                'company_license_id' => $license->getKey(),
                'group_id' => $license->group_id,
                'company_id' => $license->company_id,
                'reference_month' => $data->referenceMonth->toDateString(),
                'amount_cents' => $data->amountCents,
                'due_date' => $data->dueDate->toDateString(),
                'status' => CompanyLicenseInvoiceStatus::Pending,
                'boleto_number' => $data->boletoNumber,
                'boleto_url' => $data->boletoUrl,
                'boleto_pdf_url' => $data->boletoPdfUrl,
                'created_by_user_id' => $actor->getKey(),
            ]);

            $license->forceFill([
                'status' => CompanyLicenseStatus::PendingPayment,
                'suspended_at' => null,
            ])->save();

            return $invoice;
        });

        return $invoice;
    }
}
