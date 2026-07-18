<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Domain\Tenancy\Actions\RegisterOwnerAndCompany;
use App\Domain\Tenancy\Data\RegisterOwnerAndCompanyData;
use App\Http\Requests\Tenancy\RegisterOwnerAndCompanyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class RegisterOwnerAndCompanyController
{
    public function __invoke(RegisterOwnerAndCompanyRequest $request, RegisterOwnerAndCompany $action): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        $result = $action->execute(new RegisterOwnerAndCompanyData(
            userName: $validated['name'],
            userEmail: $validated['email'],
            password: $validated['password'],
            groupName: $validated['group_name'],
            companyLegalName: $validated['company_legal_name'],
            companyTradeName: $validated['company_trade_name'],
            companyCnpj: $validated['company_cnpj'],
            taxRegime: $validated['tax_regime'] ?? 'simples',
            companyZipCode: $validated['company_zip_code'] ?? null,
            companyStreet: $validated['company_street'] ?? null,
            companyNumber: $validated['company_number'] ?? null,
            companyComplement: $validated['company_complement'] ?? null,
            companyDistrict: $validated['company_district'] ?? null,
            companyCity: $validated['company_city'] ?? null,
            companyState: $validated['company_state'] ?? null,
            companyPhone: $validated['company_phone'] ?? null,
            companyEmail: $validated['company_email'] ?? null,
        ));

        if (! $request->expectsJson()) {
            Auth::login($result->user);
            $request->session()->regenerate();
            $result->user->sendEmailVerificationNotification();

            return redirect()
                ->route('verification.notice')
                ->with('status', 'Conta criada com sucesso. Confirme seu e-mail para acessar o painel.');
        }

        return response()->json([
            'user_id' => $result->user->getKey(),
            'group_id' => $result->group->getKey(),
            'company_id' => $result->company->getKey(),
        ], 201);
    }
}
