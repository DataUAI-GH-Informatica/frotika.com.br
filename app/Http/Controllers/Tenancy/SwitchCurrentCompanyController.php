<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Domain\Tenancy\Actions\SwitchCurrentCompany;
use App\Http\Requests\Tenancy\SwitchCurrentCompanyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class SwitchCurrentCompanyController
{
    public function __invoke(SwitchCurrentCompanyRequest $request, SwitchCurrentCompany $action): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $validated = $request->validated();

        $company = $action->execute($user, (int) $validated['company_id']);

        $request->session()->put('current_group_id', $company->getAttribute('group_id'));
        $request->session()->put('current_company_id', $company->getKey());

        if ($request->expectsJson()) {
            return response()->json([
                'group_id' => $company->getAttribute('group_id'),
                'company_id' => $company->getKey(),
            ]);
        }

        return redirect()
            ->back()
            ->with('status', 'Empresa ativa atualizada com sucesso.');
    }
}
