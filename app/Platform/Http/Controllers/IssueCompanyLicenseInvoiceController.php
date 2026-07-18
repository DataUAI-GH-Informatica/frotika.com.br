<?php

declare(strict_types=1);

namespace App\Platform\Http\Controllers;

use App\Domain\Billing\Actions\IssueManualCompanyLicenseInvoice;
use App\Domain\Billing\Data\IssueManualCompanyLicenseInvoiceData;
use App\Domain\Billing\Models\CompanyLicense;
use App\Models\User;
use App\Platform\Http\Requests\IssueCompanyLicenseInvoiceRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;

final class IssueCompanyLicenseInvoiceController
{
    public function __invoke(
        IssueCompanyLicenseInvoiceRequest $request,
        CompanyLicense $license,
        IssueManualCompanyLicenseInvoice $action,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $validated = $request->validated();

        $dueDate = CarbonImmutable::parse($validated['due_date']);
        $referenceMonth = isset($validated['reference_month'])
            ? CarbonImmutable::createFromFormat('Y-m', $validated['reference_month'])->startOfMonth()
            : $dueDate->startOfMonth();

        $action->execute(
            $user,
            $license,
            new IssueManualCompanyLicenseInvoiceData(
                amountCents: (int) $validated['amount_cents'],
                dueDate: $dueDate,
                referenceMonth: $referenceMonth,
                boletoNumber: $validated['boleto_number'] ?? null,
                boletoUrl: $validated['boleto_url'] ?? null,
                boletoPdfUrl: $validated['boleto_pdf_url'] ?? null,
            ),
        );

        return redirect()
            ->route('platform.groups.show', ['group' => $license->group_id])
            ->with('status', 'Boleto lançado com sucesso para a empresa selecionada.');
    }
}
