<?php

declare(strict_types=1);

namespace App\Domain\Finance\Enums;

enum FinancialEntryPaymentMethod: string
{
    case Pix = 'pix';
    case BankSlip = 'bank_slip';
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case CreditCard = 'credit_card';
    case DebitCard = 'debit_card';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Pix => 'Pix',
            self::BankSlip => 'Boleto',
            self::BankTransfer => 'Transferencia bancaria',
            self::Cash => 'Dinheiro',
            self::CreditCard => 'Cartao de credito',
            self::DebitCard => 'Cartao de debito',
            self::Other => 'Outro',
        };
    }
}
