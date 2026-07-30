<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Import;

use App\Domain\Fuelings\Import\Exceptions\InvalidFuelingSheetException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * Traduz o arquivo XLSX na lista de linhas cruas do contrato. Só isso: não sabe
 * o que é um abastecimento, não fala com o banco. Validar valor e resolver
 * vínculo é trabalho do FuelingRowParser e da Action de importação.
 *
 * Sempre lê a PRIMEIRA aba, não a que ficou selecionada quando o arquivo foi
 * salvo — a planilha modelo tem uma segunda aba com as instruções.
 */
final class FuelingSheetReader
{
    /**
     * @return list<FuelingImportRow>
     *
     * @throws InvalidFuelingSheetException
     */
    public function read(string $path): array
    {
        $reader = new Xlsx;
        $reader->setReadEmptyCells(false);
        $reader->setReadFilter(new FuelingSheetReadFilter);

        try {
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getSheet(0);
        } catch (Throwable) {
            throw InvalidFuelingSheetException::unreadable();
        }

        try {
            $rows = $this->rows($sheet, $this->headerMap($sheet));
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        if ($rows === []) {
            throw InvalidFuelingSheetException::withoutRows();
        }

        return $rows;
    }

    /**
     * @param  array<string, int>  $map
     * @return list<FuelingImportRow>
     */
    private function rows(Worksheet $sheet, array $map): array
    {
        $rows = [];
        $lastRow = min($sheet->getHighestDataRow(), FuelingImportSheet::MAX_ROWS + 2);

        for ($number = 2; $number <= $lastRow; $number++) {
            $cells = [];

            foreach ($map as $column => $index) {
                $cells[$column] = $this->cell($sheet, $index, $number);
            }

            $row = new FuelingImportRow($number, $cells);

            // Linha em branco no meio da planilha é ruído de edição, não erro.
            if ($row->isEmpty()) {
                continue;
            }

            if (count($rows) >= FuelingImportSheet::MAX_ROWS) {
                throw InvalidFuelingSheetException::tooManyRows();
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Casa o cabeçalho por nome. Coluna repetida vale a primeira; coluna que não
     * faz parte do contrato é ignorada.
     *
     * @return array<string, int>
     */
    private function headerMap(Worksheet $sheet): array
    {
        $official = FuelingImportSheet::columns();
        $lastColumn = min(
            Coordinate::columnIndexFromString($sheet->getHighestDataColumn()),
            FuelingSheetReadFilter::maxColumns(),
        );

        $map = [];

        for ($index = 1; $index <= $lastColumn; $index++) {
            $header = FuelingImportSheet::slug((string) $this->cell($sheet, $index, 1));

            if ($header === '' || ! in_array($header, $official, true) || isset($map[$header])) {
                continue;
            }

            $map[$header] = $index;
        }

        $missing = array_values(array_diff(FuelingImportSheet::requiredColumns(), array_keys($map)));

        if ($missing !== []) {
            throw InvalidFuelingSheetException::missingColumns($missing);
        }

        return $map;
    }

    /**
     * Devolve o texto da célula. Data formatada como data vem do Excel como
     * número de série: converte para o relógio de parede que o usuário digitou,
     * sem deslocar fuso — o sistema grava a data do abastecimento como ela é.
     */
    private function cell(Worksheet $sheet, int $column, int $row): ?string
    {
        if (! $sheet->cellExists([$column, $row])) {
            return null;
        }

        $cell = $sheet->getCell([$column, $row]);

        try {
            $value = $cell->getCalculatedValue();
        } catch (Throwable) {
            $value = $cell->getValue();
        }

        if ($value === null) {
            return null;
        }

        if (is_numeric($value) && Date::isDateTime($cell)) {
            return Date::excelToDateTimeObject((float) $value)->format('Y-m-d H:i:s');
        }

        $text = match (true) {
            $value instanceof RichText => $value->getPlainText(),
            is_bool($value) => $value ? '1' : '0',
            is_float($value) => rtrim(rtrim(sprintf('%.6F', $value), '0'), '.'),
            default => (string) $value,
        };

        $text = trim($text);

        return $text === '' ? null : $text;
    }
}
