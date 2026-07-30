<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Import;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Gera a planilha modelo: primeira aba com o cabeçalho oficial e uma linha de
 * exemplo preenchida, segunda aba com o que cada coluna aceita.
 *
 * As instruções ficam em outra aba de propósito. Se estivessem embaixo do
 * cabeçalho, a importação leria o texto de ajuda como um abastecimento.
 */
final class FuelingSheetTemplate
{
    /**
     * Colunas em que o zero à esquerda tem significado e não pode virar número.
     *
     * @var list<string>
     */
    private const TEXT_COLUMNS = [
        FuelingImportSheet::CODE,
        FuelingImportSheet::DRIVER_CPF,
        FuelingImportSheet::STATION_DOCUMENT,
        FuelingImportSheet::INVOICE_NUMBER,
    ];

    public function build(): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Frotika')
            ->setTitle('Modelo de importação de abastecimentos');

        $this->fillData($spreadsheet->getSheet(0));
        $this->fillInstructions($spreadsheet->createSheet(1));
        $spreadsheet->setActiveSheetIndex(0);

        return $this->render($spreadsheet);
    }

    private function fillData(Worksheet $sheet): void
    {
        $sheet->setTitle('Abastecimentos');

        $columns = FuelingImportSheet::columns();
        $example = FuelingImportSheet::example();

        foreach ($columns as $index => $column) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);

            $sheet->setCellValueExplicit($letter.'1', $column, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit($letter.'2', $example[$column], DataType::TYPE_STRING);
            $sheet->getColumnDimension($letter)->setAutoSize(true);

            if (in_array($column, self::TEXT_COLUMNS, true)) {
                $sheet->getStyle($letter)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
            }
        }

        $lastLetter = Coordinate::stringFromColumnIndex(count($columns));
        $sheet->getStyle('A1:'.$lastLetter.'1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
    }

    private function fillInstructions(Worksheet $sheet): void
    {
        $sheet->setTitle('Instruções');

        $sheet->setCellValue('A1', 'Coluna');
        $sheet->setCellValue('B1', 'Como preencher');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);

        $row = 2;

        foreach (FuelingImportSheet::hints() as $column => $hint) {
            $sheet->setCellValueExplicit('A'.$row, $column, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B'.$row, $hint, DataType::TYPE_STRING);
            $row++;
        }

        $sheet->setCellValue('A'.($row + 1), 'Idempotência');
        $sheet->setCellValue('B'.($row + 1), 'Reenviar a mesma planilha não duplica: a linha repetida é ignorada.');
        $sheet->setCellValue('A'.($row + 2), 'Limite');
        $sheet->setCellValue('B'.($row + 2), sprintf('Até %d abastecimentos por arquivo.', FuelingImportSheet::MAX_ROWS));

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setWidth(90);
    }

    private function render(Spreadsheet $spreadsheet): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'frotika-fueling-template');

        try {
            (new Xlsx($spreadsheet))->save($path);

            return (string) file_get_contents($path);
        } finally {
            unlink($path);
            $spreadsheet->disconnectWorksheets();
        }
    }
}
