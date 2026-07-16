<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Domain\Tenancy\Actions\SwitchCurrentCompany;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SwitchCurrentCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_troca_empresa_ativa_do_usuario_quando_ha_vinculo_valido(): void
    {
        $user = User::factory()->create();
        $group = $this->createGroup($user);
        $companyA = $this->createCompany($group, 'Empresa A', '12345678000111');
        $companyB = $this->createCompany($group, 'Empresa B', '12345678000112');

        $group->users()->attach($user->getKey(), [
            'role' => 'owner',
            'invited_by' => null,
            'joined_at' => now(),
        ]);
        $user->companies()->attach([$companyA->getKey(), $companyB->getKey()]);

        $user->forceFill([
            'current_group_id' => $group->getKey(),
            'current_company_id' => $companyA->getKey(),
        ])->save();

        $action = app(SwitchCurrentCompany::class);
        $result = $action->execute($user, $companyB->getKey());

        $this->assertSame($companyB->getKey(), $result->getKey());
        $this->assertDatabaseHas('users', [
            'id' => $user->getKey(),
            'current_group_id' => $group->getKey(),
            'current_company_id' => $companyB->getKey(),
        ]);
    }

    public function test_action_rejeita_troca_quando_usuario_nao_tem_acesso_a_empresa(): void
    {
        $user = User::factory()->create();
        $allowedGroup = $this->createGroup($user);
        $allowedCompany = $this->createCompany($allowedGroup, 'Empresa Permitida', '12345678000113');

        $otherOwner = User::factory()->create();
        $deniedGroup = $this->createGroup($otherOwner);
        $deniedCompany = $this->createCompany($deniedGroup, 'Empresa Negada', '12345678000114');

        $allowedGroup->users()->attach($user->getKey(), [
            'role' => 'owner',
            'invited_by' => null,
            'joined_at' => now(),
        ]);
        $user->companies()->attach([$allowedCompany->getKey()]);

        $action = app(SwitchCurrentCompany::class);

        $this->expectException(AuthorizationException::class);
        $action->execute($user, $deniedCompany->getKey());
    }

    public function test_action_lanca_erro_quando_empresa_nao_existe(): void
    {
        $user = User::factory()->create();

        $action = app(SwitchCurrentCompany::class);

        $this->expectException(ModelNotFoundException::class);
        $action->execute($user, 999999);
    }

    public function test_endpoint_troca_empresa_e_atualiza_sessao(): void
    {
        $user = User::factory()->create();
        $group = $this->createGroup($user);
        $companyA = $this->createCompany($group, 'Empresa A', '12345678000115');
        $companyB = $this->createCompany($group, 'Empresa B', '12345678000116');

        $group->users()->attach($user->getKey(), [
            'role' => 'owner',
            'invited_by' => null,
            'joined_at' => now(),
        ]);
        $user->companies()->attach([$companyA->getKey(), $companyB->getKey()]);

        $user->forceFill([
            'current_group_id' => $group->getKey(),
            'current_company_id' => $companyA->getKey(),
        ])->save();

        $response = $this
            ->actingAs($user)
            ->withSession([
                'current_group_id' => $group->getKey(),
                'current_company_id' => $companyA->getKey(),
            ])
            ->postJson(route('tenancy.switch-company'), [
                'company_id' => $companyB->getKey(),
            ]);

        $response->assertOk();
        $response->assertJson([
            'group_id' => $group->getKey(),
            'company_id' => $companyB->getKey(),
        ]);
        $response->assertSessionHas('current_group_id', $group->getKey());
        $response->assertSessionHas('current_company_id', $companyB->getKey());

        $this->assertDatabaseHas('users', [
            'id' => $user->getKey(),
            'current_group_id' => $group->getKey(),
            'current_company_id' => $companyB->getKey(),
        ]);
    }

    private function createGroup(User $owner): Group
    {
        return Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo de Teste',
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
