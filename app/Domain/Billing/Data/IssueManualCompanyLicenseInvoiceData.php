<?php

declare(strict_types=1);

namespace App\Domain\Billing\Data;

use Carbon\CarbonImmutable;

final readonly class IssueManualCompanyLicenseInvoiceData
{
    public function __construct(
        public int $amountCents,
        public CarbonImmutable $dueDate,
        public CarbonImmutable $referenceMonth,
        public ?string $boletoNumber = null,
        public ?string $boletoUrl = null,
        public ?string $boletoPdfUrl = null,
    ) {}
}
