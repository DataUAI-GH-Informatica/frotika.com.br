<?php

declare(strict_types=1);

namespace App\Support\Cnpj;

/**
 * Dados de um CNPJ normalizados a partir da BrasilAPI ou da ReceitaWS. As duas
 * fontes retornam formatos diferentes; aqui elas viram um único formato.
 */
final readonly class CnpjData
{
    public function __construct(
        public string $source,
        public ?string $legalName = null,
        public ?string $tradeName = null,
        public ?string $registrationStatus = null,
        public ?string $openedAt = null,
        public ?string $legalNature = null,
        public ?string $primaryCnae = null,
        public ?string $primaryCnaeDescription = null,
        public ?string $street = null,
        public ?string $number = null,
        public ?string $complement = null,
        public ?string $district = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $zipCode = null,
        public ?string $phone = null,
        public ?string $email = null,
    ) {}

    /**
     * Payload enxuto que o formulário de cadastro consome.
     *
     * @return array<string, string|null>
     */
    public function toFormPayload(): array
    {
        return [
            'legal_name' => $this->legalName,
            'trade_name' => $this->tradeName,
            'situacao' => $this->registrationStatus,
            'municipio' => $this->city,
            'uf' => $this->state,
            'zip_code' => $this->zipCode,
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement,
            'district' => $this->district,
            'city' => $this->city,
            'state' => $this->state,
            'phone' => $this->phone,
            'email' => $this->email,
        ];
    }
}
