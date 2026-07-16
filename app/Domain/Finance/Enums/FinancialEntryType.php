<?php

declare(strict_types=1);

namespace App\Domain\Finance\Enums;

enum FinancialEntryType: string
{
    case Revenue = 'revenue';
    case Expense = 'expense';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Revenue => 'Receita',
            self::Expense => 'Despesa',
            self::Transfer => 'Transferencia',
        };
    }
}
