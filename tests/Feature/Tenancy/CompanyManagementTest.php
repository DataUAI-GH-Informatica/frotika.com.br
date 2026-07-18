<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Domain\Billing\Enums\GroupLicenseStatus;
use App\Domain\Billing\Models\GroupLicense;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_listagem_mostra_apenas_empresas_do_grupo_atual(): void
    {
        [$owner, $group] = $this->createOwnerWithGroup();
        $this->createCompany($group, 'Filial Sul', '55111222000221');

        $otherOwner = User::factory()->create();
        $otherGroup = $this->createGroup($otherOwner, 'Outro Grupo');
        $this->createCompany($otherGroup, 'Empresa de Outro Grupo', '55111222000305');

        $response = $this
            ->actingAs($owner)
            ->get(route('companies.index'));

        $response->assertOk();
        $response->assertSee('Filial Sul');
        $response->assertDontSee('Empresa de Outro Grupo');
    }

    public function test_owner_cadastra_nova_empresa_com_plano_de_contas_e_conta_caixa(): void
    {
        [$owner, $group] = $this->createOwnerWithGroup();

        $response = $this
            ->actingAs($owner)
            ->post(route('companies.store'), [
                'legal_name' => 'Transportes Nova Filial LTDA',
                'trade_name' => 'Nova Filial',
                'cnpj' => '11222333000181',
                'tax_regime' => 'simples',
                'city' => 'Curitiba',
                'state' => 'pr',
            ]);

        $company = Company::query()->where('cnpj', '11222333000181')->firstOrFail();

        $response->assertRedirect(route('companies.show', ['company' => $company->getKey()]));

        $this->assertSame($group->getKey(), (int) $company->getAttribute('group_id'));
        $this->assertSame('PR', $company->getAttribute('state'));

        // A licença continua sendo do grupo: cadastrar empresa não cria licença nova.
        $this->assertDatabaseCount('group_licenses', 1);

        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->getKey(),
            'user_id' => $owner->getKey(),
        ]);

        $this->assertDatabaseHas('bank_accounts', [
            'company_id' => $company->getKey(),
            'name' => 'Caixa',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('financial_categories', [
            'company_id' => $company->getKey(),
            'code' => '3.1',
            'name' => 'Combustivel',
        ]);
    }

    public function test_cnpj_duplicado_e_rejeitado_no_cadastro(): void
    {
        [$owner, $group] = $this->createOwnerWithGroup();
        $this->createCompany($group, 'Existente', '11222333000181');

        $response = $this
            ->actingAs($owner)
            ->from(route('companies.create'))
            ->post(route('companies.store'), [
                'legal_name' => 'Outra LTDA',
                'trade_name' => 'Outra',
                'cnpj' => '11222333000181',
                'tax_regime' => 'simples',
            ]);

        $response->assertRedirect(route('companies.create'));
        $response->assertSessionHasErrors(['cnpj']);
    }

    public function test_membro_sem_papel_de_gestao_nao_cadastra_empresa(): void
    {
        [, $group] = $this->createOwnerWithGroup();
        $member = $this->createMember($group, 'manager');

        $formResponse = $this
            ->actingAs($member)
            ->get(route('companies.create'));
        $formResponse->assertForbidden();

        $storeResponse = $this
            ->actingAs($member)
            ->post(route('companies.store'), [
                'legal_name' => 'Não Autorizada LTDA',
                'trade_name' => 'Não Autorizada',
                'cnpj' => '11222333000181',
                'tax_regime' => 'simples',
            ]);
        $storeResponse->assertForbidden();

        $this->assertDatabaseMissing('companies', ['cnpj' => '11222333000181']);
    }

    public function test_owner_edita_empresa_do_proprio_grupo(): void
    {
        [$owner, $group] = $this->createOwnerWithGroup();
        $company = $this->createCompany($group, 'Filial Leste', '55211000000122');

        $response = $this
            ->actingAs($owner)
            ->put(route('companies.update', ['company' => $company->getKey()]), [
                'legal_name' => 'Filial Leste Transportes LTDA',
                'trade_name' => 'Filial Leste Nova',
                'cnpj' => $company->getAttribute('cnpj'),
                'tax_regime' => 'presumido',
                'city' => 'Recife',
                'state' => 'pe',
            ]);

        $response->assertRedirect(route('companies.show', ['company' => $company->getKey()]));

        $this->assertDatabaseHas('companies', [
            'id' => $company->getKey(),
            'trade_name' => 'Filial Leste Nova',
            'tax_regime' => 'presumido',
            'city' => 'Recife',
            'state' => 'PE',
        ]);
    }

    public function test_nao_edita_empresa_de_outro_grupo(): void
    {
        [$owner] = $this->createOwnerWithGroup();

        $otherOwner = User::factory()->create();
        $otherGroup = $this->createGroup($otherOwner, 'Grupo Alheio');
        $otherCompany = $this->createCompany($otherGroup, 'Empresa Alheia', '55111222000305');

        $response = $this
            ->actingAs($owner)
            ->put(route('companies.update', ['company' => $otherCompany->getKey()]), [
                'legal_name' => 'Invasao LTDA',
                'trade_name' => 'Invasao',
                'cnpj' => $otherCompany->getAttribute('cnpj'),
                'tax_regime' => 'simples',
            ]);

        $response->assertForbidden();
    }

    public function test_desativa_empresa_secundaria(): void
    {
        [$owner, $group] = $this->createOwnerWithGroup();
        $company = $this->createCompany($group, 'Filial Descartável', '55111222000221');

        $response = $this
            ->actingAs($owner)
            ->delete(route('companies.destroy', ['company' => $company->getKey()]));

        $response->assertRedirect(route('companies.index'));
        $this->assertSoftDeleted('companies', ['id' => $company->getKey()]);
    }

    public function test_nao_desativa_empresa_principal(): void
    {
        [$owner, , $primary] = $this->createOwnerWithGroup();

        // A empresa ativa é a principal; troca a ativa para outra para isolar a regra da principal.
        $secondary = $this->createCompany($primary->group()->firstOrFail(), 'Secundária', '55111222000221');
        $owner->companies()->attach($secondary->getKey());
        $owner->forceFill(['current_company_id' => $secondary->getKey()])->save();

        $response = $this
            ->actingAs($owner)
            ->from(route('companies.show', ['company' => $primary->getKey()]))
            ->delete(route('companies.destroy', ['company' => $primary->getKey()]));

        $response->assertRedirect(route('companies.show', ['company' => $primary->getKey()]));
        $response->assertSessionHasErrors(['company']);
        $this->assertNotSoftDeleted('companies', ['id' => $primary->getKey()]);
    }

    public function test_nao_desativa_empresa_ativa(): void
    {
        [$owner, $group, $primary] = $this->createOwnerWithGroup();
        $current = $this->createCompany($group, 'Empresa Atual', '55111222000221');
        $owner->companies()->attach($current->getKey());
        $owner->forceFill(['current_company_id' => $current->getKey()])->save();

        $response = $this
            ->actingAs($owner)
            ->from(route('companies.show', ['company' => $current->getKey()]))
            ->delete(route('companies.destroy', ['company' => $current->getKey()]));

        $response->assertRedirect(route('companies.show', ['company' => $current->getKey()]));
        $response->assertSessionHasErrors(['company']);
        $this->assertNotSoftDeleted('companies', ['id' => $current->getKey()]);
    }

    /**
     * @return array{User, Group, Company}
     */
    private function createOwnerWithGroup(): array
    {
        $owner = User::factory()->create();
        $group = $this->createGroup($owner, 'Grupo Principal');

        GroupLicense::query()->create([
            'group_id' => $group->getKey(),
            'status' => GroupLicenseStatus::Active,
            'trial_starts_at' => now()->subDays(30),
            'activated_at' => now()->subDays(20),
            'monthly_price_cents' => 9900,
        ]);

        $primary = $this->createCompany($group, 'Matriz', '55111222000112');

        $group->users()->attach($owner->getKey(), [
            'role' => 'owner',
            'invited_by' => null,
            'joined_at' => now(),
        ]);

        $owner->companies()->attach($primary->getKey());

        $owner->forceFill([
            'current_group_id' => $group->getKey(),
            'current_company_id' => $primary->getKey(),
        ])->save();

        return [$owner, $group, $primary];
    }

    private function createMember(Group $group, string $role): User
    {
        $member = User::factory()->create();

        $group->users()->attach($member->getKey(), [
            'role' => $role,
            'invited_by' => null,
            'joined_at' => now(),
        ]);

        $primaryCompanyId = (int) $group->refresh()->primary_company_id;
        $member->companies()->attach($primaryCompanyId);

        $member->forceFill([
            'current_group_id' => $group->getKey(),
            'current_company_id' => $primaryCompanyId,
        ])->save();

        return $member;
    }

    private function createGroup(User $owner, string $name): Group
    {
        return Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => $name,
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);
    }

    private function createCompany(Group $group, string $name, string $cnpj): Company
    {
        return Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => $cnpj,
            'legal_name' => $name,
            'trade_name' => $name,
            'tax_regime' => 'simples',
        ]);
    }
}
