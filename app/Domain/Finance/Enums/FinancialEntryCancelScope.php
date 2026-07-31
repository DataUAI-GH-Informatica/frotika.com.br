<?php

declare(strict_types=1);

namespace App\Domain\Finance\Enums;

enum FinancialEntryCancelScope: string
{
    case Single = 'single';
    case Forward = 'forward';
    case All = 'all';
}
