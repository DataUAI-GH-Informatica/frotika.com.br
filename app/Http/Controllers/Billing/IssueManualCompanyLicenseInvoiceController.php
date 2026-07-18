<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\Actions\IssueManualCompanyLicenseInvoice;
use App\Domain\Billing\Data\IssueManualCompanyLicenseInvoiceData;
use App\Domain\Billing\Models\CompanyLicense;
use App\Http\Requests\Billing\IssueManualCompanyLicenseInvoiceRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;

final class IssueManualCompanyLicenseInvoiceController
{
    public function __invoke(
        IssueManualCompanyLicenseInvoiceRequest $request,
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
            ->route('billing.licenses.index')
            ->with('status', 'Boleto lançado com sucesso para a licença selecionada.');
    }
}
