<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Import;

/**
 * Contrato da planilha de importação de abastecimentos: uma linha por
 * abastecimento, cabeçalho semântico em pt-BR na primeira linha.
 *
 * O cabeçalho é reconhecido por nome, não por posição — reordenar colunas ou
 * apagar uma coluna opcional não quebra a importação. Colunas desconhecidas são
 * ignoradas, o que deixa o cliente manter as anotações dele na mesma planilha.
 *
 * Os valores de enum são os do sistema (`diesel_s10`, `fuel_card`...), mas o
 * rótulo pt-BR também é aceito: quem preenche à mão digita "Diesel S10".
 */
final class FuelingImportSheet
{
    /**
     * Teto de linhas por arquivo. Um lote é um único job: o limite é o que
     * mantém memória e tempo de fila previsíveis nesta primeira versão.
     */
    public const MAX_ROWS = 1000;

    public const CODE = 'codigo_abastecimento';

    public const PLATE = 'placa_veiculo';

    public const FUELED_AT = 'data_hora_abastecimento';

    public const ODOMETER = 'odometro_km';

    public const PRODUCT = 'produto';

    public const LITERS = 'litros';

    public const PRICE_PER_LITER = 'preco_por_litro';

    public const TOTAL = 'valor_total';

    public const TANK = 'tanque';

    public const FULL_TANK = 'tanque_cheio';

    public const PAYMENT_METHOD = 'forma_pagamento';

    public const DRIVER_CPF = 'cpf_motorista';

    public const STATION_DOCUMENT = 'posto_cnpj';

    public const STATION_LEGAL_NAME = 'posto_razao_social';

    public const STATION_TRADE_NAME = 'posto_nome_fantasia';

    public const STATION_CITY = 'posto_cidade';

    public const STATION_STATE = 'posto_uf';

    public const INVOICE_NUMBER = 'numero_cupom';

    public const NOTES = 'observacoes';

    /**
     * Ordem oficial das colunas — a mesma da planilha modelo.
     *
     * @return list<string>
     */
    public static function columns(): array
    {
        return [
            self::CODE,
            self::PLATE,
            self::FUELED_AT,
            self::ODOMETER,
            self::PRODUCT,
            self::LITERS,
            self::PRICE_PER_LITER,
            self::TOTAL,
            self::TANK,
            self::FULL_TANK,
            self::PAYMENT_METHOD,
            self::DRIVER_CPF,
            self::STATION_DOCUMENT,
            self::STATION_LEGAL_NAME,
            self::STATION_TRADE_NAME,
            self::STATION_CITY,
            self::STATION_STATE,
            self::INVOICE_NUMBER,
            self::NOTES,
        ];
    }

    /**
     * Colunas sem as quais nem dá para tentar importar a linha.
     *
     * @return list<string>
     */
    public static function requiredColumns(): array
    {
        return [
            self::PLATE,
            self::FUELED_AT,
            self::ODOMETER,
            self::PRODUCT,
            self::LITERS,
            self::TOTAL,
            self::PAYMENT_METHOD,
        ];
    }

    /**
     * Linha de exemplo da planilha modelo.
     *
     * @return array<string, string>
     */
    public static function example(): array
    {
        return [
            self::CODE => 'AB-1001',
            self::PLATE => 'RTA4B56',
            self::FUELED_AT => '01/07/2026 08:30',
            self::ODOMETER => '158420',
            self::PRODUCT => 'diesel_s10',
            self::LITERS => '180,500',
            self::PRICE_PER_LITER => '5,899',
            self::TOTAL => '1.064,77',
            self::TANK => 'main',
            self::FULL_TANK => 'sim',
            self::PAYMENT_METHOD => 'fuel_card',
            self::DRIVER_CPF => '529.982.247-25',
            self::STATION_DOCUMENT => '11.222.333/0001-81',
            self::STATION_LEGAL_NAME => 'Auto Posto Rodovia LTDA',
            self::STATION_TRADE_NAME => 'Posto Rodovia',
            self::STATION_CITY => 'Uberlândia',
            self::STATION_STATE => 'MG',
            self::INVOICE_NUMBER => '000123456',
            self::NOTES => 'Abastecimento na volta de São Paulo',
        ];
    }

    /**
     * Texto de ajuda por coluna, escrito na terceira linha da planilha modelo.
     *
     * @return array<string, string>
     */
    public static function hints(): array
    {
        return [
            self::CODE => 'opcional · seu código do abastecimento; se repetir, a linha é ignorada',
            self::PLATE => 'obrigatório · veículo já cadastrado no Frotika',
            self::FUELED_AT => 'obrigatório · dd/mm/aaaa hh:mm',
            self::ODOMETER => 'obrigatório · km inteiro do painel',
            self::PRODUCT => 'obrigatório · diesel_s10, diesel_s500, arla32, gasoline, ethanol, cng, oil',
            self::LITERS => 'obrigatório · até 3 decimais',
            self::PRICE_PER_LITER => 'opcional · em branco, calcula por valor_total ÷ litros',
            self::TOTAL => 'obrigatório · valor pago em reais',
            self::TANK => 'opcional · main (padrão) ou auxiliary',
            self::FULL_TANK => 'opcional · sim ou não; só entre dois "sim" o km/l é calculado',
            self::PAYMENT_METHOD => 'obrigatório · cash, pix, fuel_card, credit, debit, invoice',
            self::DRIVER_CPF => 'opcional · motorista já cadastrado',
            self::STATION_DOCUMENT => 'opcional · CNPJ do posto; cadastra o posto se ainda não existir',
            self::STATION_LEGAL_NAME => 'opcional · razão social do posto',
            self::STATION_TRADE_NAME => 'opcional · nome fantasia do posto',
            self::STATION_CITY => 'opcional · cidade do posto',
            self::STATION_STATE => 'opcional · UF com 2 letras',
            self::INVOICE_NUMBER => 'opcional · número da nota ou do cupom',
            self::NOTES => 'opcional · até 2000 caracteres',
        ];
    }

    /**
     * Reduz um texto ao identificador comparável: minúsculas, sem acento e com
     * separador único. Usado tanto para casar o cabeçalho quanto para aceitar o
     * rótulo pt-BR de um enum no lugar do valor técnico.
     */
    public static function slug(string $value): string
    {
        $ascii = strtr(mb_strtolower(trim($value)), [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ]);

        $slug = preg_replace('/[^a-z0-9]+/', '_', $ascii) ?? '';

        return trim($slug, '_');
    }
}
