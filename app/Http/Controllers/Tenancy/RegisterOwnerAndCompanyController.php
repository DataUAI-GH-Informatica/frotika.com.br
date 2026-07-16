<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenancy;

use App\Domain\Tenancy\Actions\RegisterOwnerAndCompany;
use App\Domain\Tenancy\Data\RegisterOwnerAndCompanyData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class RegisterOwnerAndCompanyController
{
    public function __invoke(Request $request, RegisterOwnerAndCompany $action): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'group_name' => ['required', 'string', 'max:120'],
            'company_legal_name' => ['required', 'string', 'max:150'],
            'company_trade_name' => ['required', 'string', 'max:150'],
            'company_cnpj' => ['required', 'string', 'size:14'],
            'tax_regime' => ['nullable', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $validated = $validator->validated();

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
