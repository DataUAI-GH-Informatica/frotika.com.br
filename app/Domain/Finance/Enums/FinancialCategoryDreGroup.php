<?php

declare(strict_types=1);

namespace App\Domain\Finance\Enums;

enum FinancialCategoryDreGroup: string
{
    case GrossRevenue = 'gross_revenue';
    case Deductions = 'deductions';
    case VariableCost = 'variable_cost';
    case FixedCost = 'fixed_cost';
    case AdminExpense = 'admin_expense';
    case FinancialExpense = 'financial_expense';
    case NonOperating = 'non_operating';
    case Investment = 'investment';

    public function label(): string
    {
        return match ($this) {
            self::GrossRevenue => 'Receita bruta',
            self::Deductions => 'Deducoes',
            self::VariableCost => 'Custos variaveis',
            self::FixedCost => 'Custos fixos',
            self::AdminExpense => 'Despesas administrativas',
            self::FinancialExpense => 'Despesas financeiras',
            self::NonOperating => 'Nao operacional',
            self::Investment => 'Investimento',
        };
    }
}
