<?php

declare(strict_types=1);

namespace App\Domain\Billing\Enums;

enum CompanyLicenseInvoiceStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Paid => 'Pago',
            self::Overdue => 'Vencido',
            self::Canceled => 'Cancelado',
        };
    }
}
