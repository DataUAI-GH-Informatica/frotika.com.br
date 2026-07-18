<?php

declare(strict_types=1);

namespace App\Platform\Http\Controllers;

use App\Domain\Billing\Actions\MarkGroupLicenseInvoiceAsPaid;
use App\Domain\Billing\Models\GroupLicenseInvoice;
use App\Models\User;
use App\Platform\Http\Requests\MarkGroupLicenseInvoicePaidRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;

final class MarkGroupLicenseInvoicePaidController
{
    public function __invoke(
        MarkGroupLicenseInvoicePaidRequest $request,
        GroupLicenseInvoice $invoice,
        MarkGroupLicenseInvoiceAsPaid $action,
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
