<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Domain\Tenancy\Actions\RegisterOwnerAndCompany;
use App\Domain\Tenancy\Data\RegisterOwnerAndCompanyData;
use App\Http\Requests\Tenancy\RegisterOwnerAndCompanyRequest;
use Illuminate\Http\JsonResponse;

final class RegisterOwnerAndCompanyController
{
    public function __invoke(RegisterOwnerAndCompanyRequest $request, RegisterOwnerAndCompany $action): JsonResponse
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
        ));

        return response()->json([
            'user_id' => $result->user->getKey(),
            'group_id' => $result->group->getKey(),
            'company_id' => $result->company->getKey(),
        ], 201);
    }
}
