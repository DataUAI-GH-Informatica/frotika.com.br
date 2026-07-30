<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Import;

/**
 * Uma linha crua da planilha, já indexada pelo nome oficial da coluna. Guarda o
 * número real da linha no arquivo para que a tela de resultado aponte exatamente
 * onde o cliente precisa corrigir.
 */
final readonly class FuelingImportRow
{
    /**
     * @param  array<string, string|null>  $cells
     */
    public function __construct(
        public int $number,
        private array $cells,
    ) {}

    public function text(string $column): ?string
    {
        return $this->cells[$column] ?? null;
    }

    public function isEmpty(): bool
    {
        foreach ($this->cells as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }
}
