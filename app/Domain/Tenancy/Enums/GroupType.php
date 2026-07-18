<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Enums;

enum GroupType: string
{
    case Customer = 'customer';
    case Platform = 'platform';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Cliente',
            self::Platform => 'Plataforma',
        };
    }
}
