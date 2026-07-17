<?php

declare(strict_types=1);

namespace App\Support\Cnpj;

enum CnpjLookupStatus: string
{
    case Found = 'found';
    case NotFound = 'not_found';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::Found => 'Empresa encontrada',
            self::NotFound => 'CNPJ não encontrado na Receita',
            self::Unavailable => 'Consulta indisponível no momento',
        };
    }
}
