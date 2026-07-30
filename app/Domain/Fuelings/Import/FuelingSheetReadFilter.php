<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Import;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Limita a leitura à janela que o contrato prevê. Sem isso, um arquivo de 4 MB
 * com centenas de milhares de linhas seria carregado inteiro na memória só para
 * então ser recusado.
 *
 * A janela vai até MAX_ROWS + 2 (cabeçalho + uma linha além do teto) justamente
 * para conseguir detectar que o arquivo passou do limite.
 */
final class FuelingSheetReadFilter implements IReadFilter
{
    /**
     * Folga de colunas: o cabeçalho é casado por nome, então o cliente pode ter
     * colunas próprias antes ou entre as oficiais.
     */
    private const MAX_COLUMNS = 40;

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        if ($row > FuelingImportSheet::MAX_ROWS + 2) {
            return false;
        }

        return strlen($columnAddress) <= 2 && self::columnIndex($columnAddress) <= self::MAX_COLUMNS;
    }

    public static function maxColumns(): int
    {
        return self::MAX_COLUMNS;
    }

    private static function columnIndex(string $columnAddress): int
    {
        $index = 0;

        foreach (str_split(strtoupper($columnAddress)) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }

        return $index;
    }
}
