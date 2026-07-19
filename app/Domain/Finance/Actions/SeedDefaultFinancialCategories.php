<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\FinancialCategoryAllocation;
use App\Domain\Finance\Enums\FinancialCategoryDreGroup;
use App\Domain\Finance\Enums\FinancialCategoryType;
use App\Domain\Finance\Models\FinancialCategory;
use App\Domain\Tenancy\Models\Company;
use App\Support\Tenancy\TenantContext;

final class SeedDefaultFinancialCategories
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function execute(Company $company): void
    {
        $this->tenant->runFor($company, function (): void {
            $categoryIdsByCode = [];

            foreach ($this->blueprintCategories() as $categoryData) {
                $parentCode = $categoryData['parent_code'];
                unset($categoryData['parent_code']);

                $categoryData = $this->normalizeEnumValues($categoryData);

                $categoryData['parent_id'] = $parentCode === null
                    ? null
                    : $categoryIdsByCode[$parentCode] ?? null;

                $category = FinancialCategory::query()->create($categoryData);
                $categoryIdsByCode[$categoryData['code']] = $category->getKey();
            }
        });
    }

    /**
     * @param  array{
     *     code: string,
     *     name: string,
     *     type: string|null,
     *     dre_group: string|null,
     *     allocation: string|null,
     *     affects_cashflow: bool,
     *     is_system: bool,
     *     active: bool,
     *     sort_order: int,
     *     parent_id?: int|null
     * }  $categoryData
     * @return array{
     *     code: string,
     *     name: string,
     *     type: string|null,
     *     dre_group: string|null,
     *     allocation: string|null,
     *     affects_cashflow: bool,
     *     is_system: bool,
     *     active: bool,
     *     sort_order: int,
     *     parent_id?: int|null
     * }
     */
    private function normalizeEnumValues(array $categoryData): array
    {
        $categoryData['type'] = $categoryData['type'] === null
            ? null
            : FinancialCategoryType::from($categoryData['type'])->value;

        $categoryData['dre_group'] = $categoryData['dre_group'] === null
            ? null
            : FinancialCategoryDreGroup::from($categoryData['dre_group'])->value;

        $categoryData['allocation'] = $categoryData['allocation'] === null
            ? null
            : FinancialCategoryAllocation::from($categoryData['allocation'])->value;

        return $categoryData;
    }

    /**
     * @return list<array{
     *     parent_code: string|null,
     *     code: string,
     *     name: string,
     *     type: string|null,
     *     dre_group: string|null,
     *     allocation: string|null,
     *     affects_cashflow: bool,
     *     is_system: bool,
     *     active: bool,
     *     sort_order: int
     * }>
     */
    private function blueprintCategories(): array
    {
        return [
            ['parent_code' => null, 'code' => '1', 'name' => 'Receitas', 'type' => null, 'dre_group' => null, 'allocation' => null, 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 100],
            ['parent_code' => '1', 'code' => '1.1', 'name' => 'Receita de fretes', 'type' => 'revenue', 'dre_group' => 'gross_revenue', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => true, 'active' => true, 'sort_order' => 110],
            ['parent_code' => '1', 'code' => '1.2', 'name' => 'Receita de aluguel de veiculo', 'type' => 'revenue', 'dre_group' => 'gross_revenue', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 120],
            ['parent_code' => '1', 'code' => '1.3', 'name' => 'Outras receitas', 'type' => 'revenue', 'dre_group' => 'non_operating', 'allocation' => 'non_vehicle', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 130],

            ['parent_code' => null, 'code' => '2', 'name' => 'Deducoes da receita', 'type' => null, 'dre_group' => null, 'allocation' => null, 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 200],
            ['parent_code' => '2', 'code' => '2.1', 'name' => 'ICMS sobre fretes', 'type' => 'expense', 'dre_group' => 'deductions', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => true, 'active' => true, 'sort_order' => 210],
            ['parent_code' => '2', 'code' => '2.2', 'name' => 'PIS/COFINS', 'type' => 'expense', 'dre_group' => 'deductions', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 220],
            ['parent_code' => '2', 'code' => '2.3', 'name' => 'Comissao sobre frete', 'type' => 'expense', 'dre_group' => 'deductions', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 230],

            ['parent_code' => null, 'code' => '3', 'name' => 'Custos variaveis', 'type' => null, 'dre_group' => null, 'allocation' => null, 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 300],
            ['parent_code' => '3', 'code' => '3.1', 'name' => 'Combustivel', 'type' => 'expense', 'dre_group' => 'variable_cost', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => true, 'active' => true, 'sort_order' => 310],
            ['parent_code' => '3', 'code' => '3.2', 'name' => 'Arla 32', 'type' => 'expense', 'dre_group' => 'variable_cost', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => true, 'active' => true, 'sort_order' => 320],
            ['parent_code' => '3', 'code' => '3.3', 'name' => 'Pedagio', 'type' => 'expense', 'dre_group' => 'variable_cost', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 330],
            ['parent_code' => '3', 'code' => '3.4', 'name' => 'Manutencao corretiva', 'type' => 'expense', 'dre_group' => 'variable_cost', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => true, 'active' => true, 'sort_order' => 340],
            ['parent_code' => '3', 'code' => '3.5', 'name' => 'Pneus', 'type' => 'expense', 'dre_group' => 'variable_cost', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 350],
            ['parent_code' => '3', 'code' => '3.6', 'name' => 'Lubrificantes', 'type' => 'expense', 'dre_group' => 'variable_cost', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 360],
            ['parent_code' => '3', 'code' => '3.7', 'name' => 'Diarias e adiantamentos', 'type' => 'expense', 'dre_group' => 'variable_cost', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 370],
            ['parent_code' => '3', 'code' => '3.8', 'name' => 'Chapa / carga e descarga', 'type' => 'expense', 'dre_group' => 'variable_cost', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 380],

            ['parent_code' => null, 'code' => '4', 'name' => 'Custos fixos do veiculo', 'type' => null, 'dre_group' => null, 'allocation' => null, 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 400],
            ['parent_code' => '4', 'code' => '4.1', 'name' => 'Salario do motorista', 'type' => 'expense', 'dre_group' => 'fixed_cost', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 410],
            ['parent_code' => '4', 'code' => '4.2', 'name' => 'Encargos do motorista', 'type' => 'expense', 'dre_group' => 'fixed_cost', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 420],
            ['parent_code' => '4', 'code' => '4.3', 'name' => 'Manutencao preventiva', 'type' => 'expense', 'dre_group' => 'fixed_cost', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 430],
            ['parent_code' => '4', 'code' => '4.4', 'name' => 'Seguro do veiculo', 'type' => 'expense', 'dre_group' => 'fixed_cost', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 440],
            ['parent_code' => '4', 'code' => '4.5', 'name' => 'IPVA e licenciamento', 'type' => 'expense', 'dre_group' => 'fixed_cost', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 450],
            ['parent_code' => '4', 'code' => '4.6', 'name' => 'Rastreamento', 'type' => 'expense', 'dre_group' => 'fixed_cost', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 460],
            ['parent_code' => '4', 'code' => '4.7', 'name' => 'Parcela de financiamento', 'type' => 'expense', 'dre_group' => 'fixed_cost', 'allocation' => 'vehicle_direct', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 470],

            ['parent_code' => null, 'code' => '5', 'name' => 'Despesas administrativas', 'type' => null, 'dre_group' => null, 'allocation' => null, 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 500],
            ['parent_code' => '5', 'code' => '5.1', 'name' => 'Pro-labore', 'type' => 'expense', 'dre_group' => 'admin_expense', 'allocation' => 'apportioned', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 510],
            ['parent_code' => '5', 'code' => '5.2', 'name' => 'Salarios do escritorio', 'type' => 'expense', 'dre_group' => 'admin_expense', 'allocation' => 'apportioned', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 520],
            ['parent_code' => '5', 'code' => '5.3', 'name' => 'Contabilidade', 'type' => 'expense', 'dre_group' => 'admin_expense', 'allocation' => 'apportioned', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 530],
            ['parent_code' => '5', 'code' => '5.4', 'name' => 'Aluguel e condominio', 'type' => 'expense', 'dre_group' => 'admin_expense', 'allocation' => 'apportioned', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 540],
            ['parent_code' => '5', 'code' => '5.5', 'name' => 'Energia, agua, internet', 'type' => 'expense', 'dre_group' => 'admin_expense', 'allocation' => 'apportioned', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 550],
            ['parent_code' => '5', 'code' => '5.6', 'name' => 'Software e sistemas', 'type' => 'expense', 'dre_group' => 'admin_expense', 'allocation' => 'apportioned', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 560],
            ['parent_code' => '5', 'code' => '5.7', 'name' => 'Outras despesas adm.', 'type' => 'expense', 'dre_group' => 'admin_expense', 'allocation' => 'apportioned', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 570],

            ['parent_code' => null, 'code' => '6', 'name' => 'Despesas financeiras', 'type' => null, 'dre_group' => null, 'allocation' => null, 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 600],
            ['parent_code' => '6', 'code' => '6.1', 'name' => 'Juros e multas', 'type' => 'expense', 'dre_group' => 'financial_expense', 'allocation' => 'apportioned', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 610],
            ['parent_code' => '6', 'code' => '6.2', 'name' => 'Tarifas bancarias', 'type' => 'expense', 'dre_group' => 'financial_expense', 'allocation' => 'apportioned', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 620],
            ['parent_code' => '6', 'code' => '6.3', 'name' => 'Antecipacao de recebiveis', 'type' => 'expense', 'dre_group' => 'financial_expense', 'allocation' => 'apportioned', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 630],

            ['parent_code' => null, 'code' => '7', 'name' => 'Investimentos', 'type' => null, 'dre_group' => null, 'allocation' => null, 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 700],
            ['parent_code' => '7', 'code' => '7.1', 'name' => 'Aquisicao de veiculo', 'type' => 'expense', 'dre_group' => 'investment', 'allocation' => 'non_vehicle', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 710],
            ['parent_code' => '7', 'code' => '7.2', 'name' => 'Aquisicao de equipamento', 'type' => 'expense', 'dre_group' => 'investment', 'allocation' => 'non_vehicle', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 720],

            ['parent_code' => null, 'code' => '8', 'name' => 'Movimentacoes nao operacionais', 'type' => null, 'dre_group' => null, 'allocation' => null, 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 800],
            ['parent_code' => '8', 'code' => '8.1', 'name' => 'Aportes de socios', 'type' => 'revenue', 'dre_group' => 'non_operating', 'allocation' => 'non_vehicle', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 810],
            ['parent_code' => '8', 'code' => '8.2', 'name' => 'Retirada de socios', 'type' => 'expense', 'dre_group' => 'non_operating', 'allocation' => 'non_vehicle', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 820],
            ['parent_code' => '8', 'code' => '8.3', 'name' => 'Emprestimo captado', 'type' => 'revenue', 'dre_group' => 'non_operating', 'allocation' => 'non_vehicle', 'affects_cashflow' => true, 'is_system' => false, 'active' => true, 'sort_order' => 830],
            ['parent_code' => '8', 'code' => '8.4', 'name' => 'Transferencia entre contas', 'type' => 'expense', 'dre_group' => 'non_operating', 'allocation' => 'non_vehicle', 'affects_cashflow' => true, 'is_system' => true, 'active' => true, 'sort_order' => 840],
        ];
    }
}
