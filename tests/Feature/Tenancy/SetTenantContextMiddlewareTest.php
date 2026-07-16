<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SetTenantContextMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/_test/tenant-context', function (TenantContext $tenantContext) {
            return response()->json([
                'company_id' => $tenantContext->companyId(),
            ]);
        });
    }

    public function test_define_contexto_pela_empresa_da_sessao_quando_usuario_tem_acesso(): void
    {
        $user = User::factory()->create();
        $group = $this->createGroup($user);
        $companyA = $this->createCompany($group, 'Empresa A', '12345678000194');
        $companyB = $this->createCompany($group, 'Empresa B', '12345678000195');

        $user->companies()->attach([$companyA->getKey(), $companyB->getKey()]);

        $response = $this
            ->actingAs($user)
            ->withSession(['current_company_id' => $companyB->getKey()])
            ->get('/_test/tenant-context');

        $response->assertOk();
        $response->assertJson(['company_id' => $companyB->getKey()]);
    }

    public function test_faz_fallback_para_current_company_id_do_usuario(): void
    {
        $user = User::factory()->create();
        $group = $this->createGroup($user);
        $company = $this->createCompany($group, 'Empresa Fallback', '12345678000196');

        $user->companies()->attach([$company->getKey()]);
        $user->forceFill([
            'current_group_id' => $group->getKey(),
            'current_company_id' => $company->getKey(),
        ])->save();

        $response = $this
            ->actingAs($user)
            ->get('/_test/tenant-context');

        $response->assertOk();
        $response->assertJson(['company_id' => $company->getKey()]);
    }

    public function test_retorna_403_quando_usuario_nao_tem_acesso_a_empresa_escolhida(): void
    {
        $user = User::factory()->create();
        $group = $this->createGroup($user);
        $companyAllowed = $this->createCompany($group, 'Empresa Permitida', '12345678000197');
        $companyDenied = $this->createCompany($group, 'Empresa Negada', '12345678000198');

        $user->companies()->attach([$companyAllowed->getKey()]);

        $response = $this
            ->actingAs($user)
            ->withSession(['current_company_id' => $companyDenied->getKey()])
            ->get('/_test/tenant-context');

        $response->assertForbidden();
    }

    public function test_sem_usuario_autenticado_contexto_permanece_nulo(): void
    {
        $response = $this->get('/_test/tenant-context');

        $response->assertOk();
        $response->assertJson(['company_id' => null]);
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
