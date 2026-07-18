<?php

declare(strict_types=1);

namespace App\Domain\Billing\Enums;

enum GroupLicenseStatus: string
{
    case Trialing = 'trialing';
    case PendingPayment = 'pending_payment';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Trialing => 'Trial',
            self::PendingPayment => 'Aguardando pagamento',
            self::Active => 'Ativa',
            self::Suspended => 'Suspensa',
        };
    }
}
