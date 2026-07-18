<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Enums;

enum FuelTank: string
{
    case Main = 'main';
    case Auxiliary = 'auxiliary';

    public function label(): string
    {
        return match ($this) {
            self::Main => 'Principal',
            self::Auxiliary => 'Auxiliar',
        };
    }
}
