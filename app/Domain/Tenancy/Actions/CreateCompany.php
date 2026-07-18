<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Actions;

use App\Domain\Finance\Actions\SeedDefaultFinancialCategories;
use App\Domain\Finance\Models\BankAccount;
use App\Domain\Tenancy\Data\CompanyData;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateCompany
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly SeedDefaultFinancialCategories $seedDefaultFinancialCategories,
    ) {}

    public function execute(User $actor, CompanyData $data): Company
    {
        Gate::forUser($actor)->authorize('create', Company::class);

        $groupId = $actor->current_group_id;

        if ($groupId === null) {
            throw ValidationException::withMessages([
                'group' => 'Nenhum grupo ativo selecionado.',
            ]);
        }

        /** @var Group $group */
        $group = Group::query()->findOrFail($groupId);

        /** @var Company $company */
        $company = DB::transaction(function () use ($actor, $group, $data): Company {
            $company = Company::query()->create([
                'group_id' => $group->getKey(),
                'uuid' => Str::uuid()->toString(),
                ...$data->toAttributes(),
            ]);

            $company->users()->attach($actor->getKey());

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

            return $company;
        });

        return $company;
    }
}
