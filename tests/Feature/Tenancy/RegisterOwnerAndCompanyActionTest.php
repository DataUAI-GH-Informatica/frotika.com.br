<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Domain\Tenancy\Actions\RegisterOwnerAndCompany;
use App\Domain\Tenancy\Data\RegisterOwnerAndCompanyData;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class RegisterOwnerAndCompanyActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_estrutura_inicial_de_tenancy_para_owner(): void
    {
        $action = app(RegisterOwnerAndCompany::class);

        $result = $action->execute(new RegisterOwnerAndCompanyData(
            userName: 'Guilherme',
            userEmail: 'guilherme@example.com',
            password: 'secret-1234',
            groupName: 'Transportes Serra Azul',
            companyLegalName: 'Transportes Serra Azul LTDA',
            companyTradeName: 'Serra Azul',
            companyCnpj: '12345678000190',
        ));

        $this->assertDatabaseHas('users', [
            'email' => 'guilherme@example.com',
            'current_group_id' => $result->group->getKey(),
            'current_company_id' => $result->company->getKey(),
        ]);

        $this->assertDatabaseHas('groups', [
            'id' => $result->group->getKey(),
            'owner_user_id' => $result->user->getKey(),
            'type' => 'customer',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('companies', [
            'id' => $result->company->getKey(),
            'group_id' => $result->group->getKey(),
            'cnpj' => '12345678000190',
            'tax_regime' => 'simples',
        ]);

        $this->assertDatabaseHas('group_user', [
            'group_id' => $result->group->getKey(),
            'user_id' => $result->user->getKey(),
            'role' => 'owner',
        ]);

        $this->assertDatabaseHas('company_user', [
            'company_id' => $result->company->getKey(),
            'user_id' => $result->user->getKey(),
        ]);
    }

    public function test_faz_rollback_quando_cnpj_duplicado_dispara_erro(): void
    {
        $existingOwner = User::factory()->create();

        $existingGroup = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo Existente',
            'type' => 'customer',
            'owner_user_id' => $existingOwner->getKey(),
            'status' => 'active',
        ]);

        Company::query()->create([
            'group_id' => $existingGroup->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '12345678000999',
            'legal_name' => 'Empresa Existente',
            'trade_name' => 'Existente',
            'tax_regime' => 'simples',
        ]);

        $action = app(RegisterOwnerAndCompany::class);

        $this->expectException(QueryException::class);

        try {
            $action->execute(new RegisterOwnerAndCompanyData(
                userName: 'Novo Dono',
                userEmail: 'novo-dono@example.com',
                password: 'secret-1234',
                groupName: 'Novo Grupo',
                companyLegalName: 'Nova Empresa LTDA',
                companyTradeName: 'Nova Empresa',
                companyCnpj: '12345678000999',
            ));
        } finally {
            $this->assertDatabaseMissing('users', [
                'email' => 'novo-dono@example.com',
            ]);

            $this->assertDatabaseCount('groups', 1);
            $this->assertDatabaseCount('companies', 1);
            $this->assertDatabaseCount('group_user', 0);
            $this->assertDatabaseCount('company_user', 0);
        }
    }
}
