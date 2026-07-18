<?php

declare(strict_types=1);

namespace App\Platform\Http\Controllers;

use App\Domain\Billing\Actions\MarkCompanyLicenseInvoiceAsPaid;
use App\Domain\Billing\Models\CompanyLicenseInvoice;
use App\Models\User;
use App\Platform\Http\Requests\MarkCompanyLicenseInvoicePaidRequest;
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
            ->route('platform.groups.show', ['group' => $invoice->group_id])
            ->with('status', 'Pagamento confirmado manualmente com sucesso.');
    }
}
