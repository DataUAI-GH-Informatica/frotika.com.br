<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Data\RegisterOwnerAndCompanyData;
use App\Domain\Tenancy\Data\RegisterOwnerAndCompanyResult;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RegisterOwnerAndCompany
{
    public function execute(RegisterOwnerAndCompanyData $data): RegisterOwnerAndCompanyResult
    {
        /** @var RegisterOwnerAndCompanyResult $result */
        $result = DB::transaction(function () use ($data): RegisterOwnerAndCompanyResult {
            $user = User::query()->create([
                'name' => $data->userName,
                'email' => $data->userEmail,
                'password' => $data->password,
            ]);

            $group = Group::query()->create([
                'uuid' => Str::uuid()->toString(),
                'name' => $data->groupName,
                'type' => 'customer',
                'owner_user_id' => $user->getKey(),
                'status' => 'active',
            ]);

            $company = Company::query()->create([
                'group_id' => $group->getKey(),
                'uuid' => Str::uuid()->toString(),
                'cnpj' => $data->companyCnpj,
                'legal_name' => $data->companyLegalName,
                'trade_name' => $data->companyTradeName,
                'tax_regime' => $data->taxRegime,
            ]);

            $group->users()->attach($user->getKey(), [
                'role' => 'owner',
                'invited_by' => null,
                'joined_at' => now(),
            ]);

            $company->users()->attach($user->getKey());

            $user->forceFill([
                'current_group_id' => $group->getKey(),
                'current_company_id' => $company->getKey(),
            ])->save();

            return new RegisterOwnerAndCompanyResult(
                user: $user->fresh(),
                group: $group->fresh(),
                company: $company->fresh(),
            );
        });

        return $result;
    }
}
