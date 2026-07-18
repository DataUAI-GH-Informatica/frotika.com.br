<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Actions;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Finance\Actions\SeedDefaultFinancialCategories;
use App\Domain\Finance\Models\BankAccount;
use App\Domain\Tenancy\Data\RegisterOwnerAndCompanyData;
use App\Domain\Tenancy\Data\RegisterOwnerAndCompanyResult;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RegisterOwnerAndCompany
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly SeedDefaultFinancialCategories $seedDefaultFinancialCategories,
    ) {}

    public function execute(RegisterOwnerAndCompanyData $data): RegisterOwnerAndCompanyResult
    {
        $trialDays = (int) config('billing.company_license_trial_days', 7);
        $monthlyPriceCents = (int) config('billing.company_license_monthly_price_cents', 9900);

        /** @var RegisterOwnerAndCompanyResult $result */
        $result = DB::transaction(function () use ($data, $trialDays): RegisterOwnerAndCompanyResult {
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
                'zip_code' => $data->companyZipCode,
                'street' => $data->companyStreet,
                'number' => $data->companyNumber,
                'complement' => $data->companyComplement,
                'district' => $data->companyDistrict,
                'city' => $data->companyCity,
                'state' => $data->companyState,
                'phone' => $data->companyPhone,
                'email' => $data->companyEmail,
            ]);

            $group->users()->attach($user->getKey(), [
                'role' => 'owner',
                'invited_by' => null,
                'joined_at' => now(),
            ]);

            $company->users()->attach($user->getKey());

            Subscription::query()->create([
                'group_id' => $group->getKey(),
                'status' => 'trialing',
                'started_at' => now(),
                'trial_ends_at' => now()->addDays($trialDays),
                'price_cents' => 0,
            ]);

            $this->seedDefaultFinancialCategories->execute($company);

            $this->tenant->runFor($company, function (): void {
                BankAccount::query()->create([
                    'name' => 'Caixa',
                    'type' => 'cash',
                    'initial_balance_cents' => 0,
                    'initial_balance_at' => now()->toDateString(),
                    'current_balance_cents' => 0,
                    'is_default' => true,
                    'active' => true,
                ]);
            });

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
