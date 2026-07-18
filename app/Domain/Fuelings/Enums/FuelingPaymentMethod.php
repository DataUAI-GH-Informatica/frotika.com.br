<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Enums;

use App\Domain\Finance\Enums\FinancialEntryPaymentMethod;

enum FuelingPaymentMethod: string
{
    case Cash = 'cash';
    case Pix = 'pix';
    case FuelCard = 'fuel_card';
    case Credit = 'credit';
    case Debit = 'debit';
    case Invoice = 'invoice';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Dinheiro',
            self::Pix => 'Pix',
            self::FuelCard => 'Cartão de abastecimento',
            self::Credit => 'Cartão de crédito',
            self::Debit => 'Cartão de débito',
            self::Invoice => 'Faturado',
        };
    }

    /**
     * Pagamento à vista sai do caixa na hora (regra 6: paid_at = fueled_at).
     * Crédito e faturado são a prazo — viram uma conta a pagar (previsto).
     */
    public function isCashLike(): bool
    {
        return match ($this) {
            self::Cash, self::Pix, self::Debit, self::FuelCard => true,
            self::Credit, self::Invoice => false,
        };
    }

    /**
     * Mapeia a forma de pagamento do abastecimento para a do lançamento
     * financeiro, que tem um vocabulário próprio.
     */
    public function toFinancialEntryPaymentMethod(): FinancialEntryPaymentMethod
    {
        return match ($this) {
            self::Cash => FinancialEntryPaymentMethod::Cash,
            self::Pix => FinancialEntryPaymentMethod::Pix,
            self::Debit => FinancialEntryPaymentMethod::DebitCard,
            self::Credit => FinancialEntryPaymentMethod::CreditCard,
            self::FuelCard, self::Invoice => FinancialEntryPaymentMethod::Other,
        };
    }
}
