<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Data;

use App\Support\Cnpj\Cnpj;

/**
 * O posto como ele vem na planilha. O CNPJ é o identificador confiável; nome,
 * cidade e UF são o que sobra quando o cliente não tem o CNPJ à mão.
 */
final readonly class FuelingStationImportData
{
    public function __construct(
        public ?string $document = null,
        public ?string $legalName = null,
        public ?string $tradeName = null,
        public ?string $city = null,
        public ?string $state = null,
    ) {}

    /**
     * O CNPJ é gravado e comparado só com dígitos. Normalizar aqui, e não em
     * quem chama, é o que garante que "11.222.333/0001-81" e "11222333000181"
     * resolvam para o mesmo posto.
     */
    public function documentDigits(): ?string
    {
        if ($this->document === null) {
            return null;
        }

        $digits = Cnpj::digits($this->document);

        return $digits === '' ? null : $digits;
    }

    public function isEmpty(): bool
    {
        return $this->document === null
            && $this->legalName === null
            && $this->tradeName === null
            && $this->city === null
            && $this->state === null;
    }

    /**
     * Nome para cadastrar ou procurar o parceiro. A razão social tem precedência
     * porque é o campo obrigatório de `business_partners`.
     */
    public function name(): ?string
    {
        return $this->legalName ?? $this->tradeName;
    }

    /**
     * Nome para o campo de texto livre do abastecimento, onde o que ajuda a
     * reconhecer o posto na listagem é o nome fantasia.
     */
    public function displayName(): ?string
    {
        return $this->tradeName ?? $this->legalName;
    }
}
