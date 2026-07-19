<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Domain\Finance\Actions\SeedDefaultFinancialCategories;
use App\Domain\Finance\Enums\FinancialCategoryAllocation;
use App\Domain\Finance\Enums\FinancialCategoryDreGroup;
use App\Domain\Finance\Enums\FinancialCategoryType;
use App\Domain\Finance\Models\FinancialCategory;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SeedDefaultFinancialCategoriesActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_semeia_plano_de_contas_padrao_com_hierarquia_e_casts_enum(): void
    {
        $company = $this->createCompany(1);

        $action = app(SeedDefaultFinancialCategories::class);
        $action->execute($company);

        $this->assertDatabaseCount('financial_categories', 45);

        $rootId = DB::table('financial_categories')
            ->where('company_id', $company->getKey())
            ->where('code', '1')
            ->value('id');

        $this->assertNotNull($rootId);

        $childParentId = DB::table('financial_categories')
            ->where('company_id', $company->getKey())
            ->where('code', '1.1')
            ->value('parent_id');

        $this->assertSame($rootId, $childParentId);

        $this->assertDatabaseHas('financial_categories', [
            'company_id' => $company->getKey(),
            'code' => '4.7',
            'name' => 'Parcela de financiamento',
        ]);

        $tenant = app(TenantContext::class);

        $category = $tenant->runFor($company, fn (): FinancialCategory => FinancialCategory::query()
            ->where('code', '1.1')
            ->firstOrFail());

        $this->assertInstanceOf(FinancialCategoryType::class, $category->type);
        $this->assertInstanceOf(FinancialCategoryDreGroup::class, $category->dre_group);
        $this->assertInstanceOf(FinancialCategoryAllocation::class, $category->allocation);
        $this->assertSame(FinancialCategoryType::Revenue, $category->type);
        $this->assertSame(FinancialCategoryDreGroup::GrossRevenue, $category->dre_group);
        $this->assertSame(FinancialCategoryAllocation::VehicleDirect, $category->allocation);
    }

    public function test_cria_copia_do_plano_por_empresa_sem_vazamento_entre_tenants(): void
    {
        $companyA = $this->createCompany(10);
        $companyB = $this->createCompany(20);

        $action = app(SeedDefaultFinancialCategories::class);
        $action->execute($companyA);
        $action->execute($companyB);

        $countA = DB::table('financial_categories')
            ->where('company_id', $companyA->getKey())
            ->count();

        $countB = DB::table('financial_categories')
            ->where('company_id', $companyB->getKey())
            ->count();

        $this->assertSame(45, $countA);
        $this->assertSame(45, $countB);
    }

    private function createCompany(int $seed): Company
    {
        $owner = User::factory()->create([
            'email' => 'owner-'.$seed.'@example.com',
        ]);

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo '.$seed,
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        return Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '12345678'.str_pad((string) $seed, 6, '0', STR_PAD_LEFT),
            'legal_name' => 'Empresa '.$seed.' LTDA',
            'trade_name' => 'Empresa '.$seed,
            'tax_regime' => 'simples',
        ]);
    }
}
