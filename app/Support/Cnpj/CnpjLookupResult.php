<?php

declare(strict_types=1);

namespace App\Support\Cnpj;

final readonly class CnpjLookupResult
{
    public function __construct(
        public CnpjLookupStatus $status,
        public ?CnpjData $data = null,
    ) {}

    public static function found(CnpjData $data): self
    {
        return new self(CnpjLookupStatus::Found, $data);
    }

    public static function notFound(): self
    {
        return new self(CnpjLookupStatus::NotFound);
    }

    public static function unavailable(): self
    {
        return new self(CnpjLookupStatus::Unavailable);
    }
}
