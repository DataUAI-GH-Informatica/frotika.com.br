<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Data;

final readonly class RegisterOwnerAndCompanyData
{
    public function __construct(
        public string $userName,
        public string $userEmail,
        public string $password,
        public string $groupName,
        public string $companyLegalName,
        public string $companyTradeName,
        public string $companyCnpj,
        public string $taxRegime = 'simples',
    ) {}
}
