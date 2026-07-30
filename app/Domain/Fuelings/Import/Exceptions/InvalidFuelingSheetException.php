<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Import\Exceptions;

use App\Domain\Fuelings\Import\FuelingImportSheet;
use RuntimeException;

/**
 * Problema no arquivo como um todo — nada foi importado. A mensagem volta para o
 * formulário de upload, por isso é escrita para o dono da transportadora ler.
 */
final class InvalidFuelingSheetException extends RuntimeException
{
    public static function unreadable(): self
    {
        return new self('Não foi possível ler a planilha. Envie um arquivo .xlsx gerado pelo Excel, Google Planilhas ou LibreOffice.');
    }

    /**
     * @param  list<string>  $columns
     */
    public static function missingColumns(array $columns): self
    {
        return new self(sprintf(
            'A planilha está sem %s: %s. Baixe a planilha modelo e mantenha a primeira linha como cabeçalho.',
            count($columns) === 1 ? 'a coluna obrigatória' : 'as colunas obrigatórias',
            implode(', ', $columns),
        ));
    }

    public static function withoutRows(): self
    {
        return new self('A planilha não tem nenhuma linha de abastecimento preenchida abaixo do cabeçalho.');
    }

    public static function tooManyRows(): self
    {
        return new self(sprintf(
            'A planilha passa de %d linhas. Divida em arquivos menores e importe um por vez.',
            FuelingImportSheet::MAX_ROWS,
        ));
    }
}
