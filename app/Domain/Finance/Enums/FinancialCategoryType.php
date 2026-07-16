<?php

declare(strict_types=1);

namespace App\Domain\Finance\Enums;

enum FinancialCategoryType: string
{
    case Revenue = 'revenue';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Revenue => 'Receita',
            self::Expense => 'Despesa',
        };
    }
}
