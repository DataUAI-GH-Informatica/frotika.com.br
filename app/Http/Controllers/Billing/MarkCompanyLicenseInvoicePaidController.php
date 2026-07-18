<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Domain\Billing\Actions\MarkCompanyLicenseInvoiceAsPaid;
use App\Domain\Billing\Models\CompanyLicenseInvoice;
use App\Http\Requests\Billing\MarkCompanyLicenseInvoicePaidRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;

final class MarkCompanyLicenseInvoicePaidController
{
    public function __invoke(
        MarkCompanyLicenseInvoicePaidRequest $request,
        CompanyLicenseInvoice $invoice,
        MarkCompanyLicenseInvoiceAsPaid $action,
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $validated = $request->validated();

        $action->execute(
            $user,
            $invoice,
            isset($validated['paid_at']) ? CarbonImmutable::parse($validated['paid_at']) : null,
            $validated['paid_note'] ?? null,
        );

        return redirect()
            ->route('billing.licenses.index')
            ->with('status', 'Pagamento confirmado manualmente com sucesso.');
    }
}
