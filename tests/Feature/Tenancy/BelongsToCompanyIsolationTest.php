<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Support\Tenancy\Exceptions\MissingTenantContextException;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogicException;
use Tests\Fixtures\Models\TenantScopedRecord;
use Tests\TestCase;

final class BelongsToCompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tenant_scoped_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id');
            $table->string('name');
        });
    }

    public function test_criar_model_sem_contexto_dispara_excecao(): void
    {
        $this->expectException(MissingTenantContextException::class);

        TenantScopedRecord::query()->create(['name' => 'Sem tenant']);
    }

    public function test_empresa_a_nao_enxerga_registros_da_empresa_b(): void
    {
        $group = $this->createGroup();
        $companyA = $this->createCompany($group, 'Empresa A', '12345678000190');
        $companyB = $this->createCompany($group, 'Empresa B', '12345678000191');

        /** @var TenantContext $tenant */
        $tenant = app(TenantContext::class);

        $tenant->runFor($companyA, fn (): TenantScopedRecord => TenantScopedRecord::query()->create(['name' => 'Registro A']));
        $tenant->runFor($companyB, fn (): TenantScopedRecord => TenantScopedRecord::query()->create(['name' => 'Registro B']));

        $recordsA = $tenant->runFor($companyA, fn (): array => TenantScopedRecord::query()->pluck('name')->all());
        $recordsB = $tenant->runFor($companyB, fn (): array => TenantScopedRecord::query()->pluck('name')->all());

        $this->assertSame(['Registro A'], $recordsA);
        $this->assertSame(['Registro B'], $recordsB);
    }

    public function test_criar_model_com_company_id_diferente_do_contexto_dispara_excecao(): void
    {
        $group = $this->createGroup();
        $companyA = $this->createCompany($group, 'Empresa A', '12345678000192');
        $companyB = $this->createCompany($group, 'Empresa B', '12345678000193');

        /** @var TenantContext $tenant */
        $tenant = app(TenantContext::class);

        $this->expectException(LogicException::class);

        $tenant->runFor($companyA, function () use ($companyB): void {
            TenantScopedRecord::query()->create([
                'company_id' => $companyB->getKey(),
                'name' => 'Registro inválido',
            ]);
        });
    }

    private function createGroup(): Group
    {
        return Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo Teste',
            'type' => 'customer',
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
