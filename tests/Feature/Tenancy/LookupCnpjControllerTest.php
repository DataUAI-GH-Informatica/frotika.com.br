<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class LookupCnpjControllerTest extends TestCase
{
    private const VALID_CNPJ = '11222333000181';

    public function test_consulta_retorna_dados_da_brasilapi(): void
    {
        Http::fake([
            'https://brasilapi.com.br/*' => Http::response([
                'razao_social' => 'Transportes Serra Azul LTDA',
                'nome_fantasia' => 'Serra Azul',
                'descricao_situacao_cadastral' => 'ATIVA',
                'logradouro' => 'Avenida Brasil',
                'numero' => '1000',
                'complemento' => 'Sala 4',
                'bairro' => 'Centro',
                'municipio' => 'SAO PAULO',
                'uf' => 'SP',
                'cep' => '01001-000',
                'ddd_telefone_1' => '(11) 99999-0000',
                'email' => 'contato@serraazul.com.br',
            ], 200),
        ]);

        $response = $this->getJson('/registrar/cnpj/'.self::VALID_CNPJ);

        $response->assertOk();
        $response->assertJson([
            'status' => 'found',
            'source' => 'brasilapi',
            'company' => [
                'legal_name' => 'Transportes Serra Azul LTDA',
                'trade_name' => 'Serra Azul',
                'situacao' => 'ATIVA',
                'municipio' => 'SAO PAULO',
                'uf' => 'SP',
                'zip_code' => '01001-000',
                'street' => 'Avenida Brasil',
                'number' => '1000',
                'complement' => 'Sala 4',
                'district' => 'Centro',
                'city' => 'SAO PAULO',
                'state' => 'SP',
                'phone' => '11999990000',
                'email' => 'contato@serraazul.com.br',
            ],
        ]);
    }

    public function test_consulta_usa_fallback_receitaws_quando_brasilapi_falha(): void
    {
        Http::fake([
            'https://brasilapi.com.br/*' => Http::response(null, 500),
            'https://receitaws.com.br/*' => Http::response([
                'status' => 'OK',
                'nome' => 'Empresa Fallback LTDA',
                'fantasia' => 'Fallback',
                'situacao' => 'ATIVA',
                'logradouro' => 'Rua das Flores',
                'numero' => '50',
                'complemento' => '',
                'bairro' => 'Jardim',
                'municipio' => 'RIO DE JANEIRO',
                'uf' => 'RJ',
                'cep' => '20000-000',
                'telefone' => '(21) 3333-4444',
                'email' => 'financeiro@fallback.com.br',
                'atividade_principal' => [
                    ['code' => '4930-2/02', 'text' => 'Transporte rodoviário de carga'],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/registrar/cnpj/'.self::VALID_CNPJ);

        $response->assertOk();
        $response->assertJsonPath('status', 'found');
        $response->assertJsonPath('source', 'receitaws');
        $response->assertJsonPath('company.legal_name', 'Empresa Fallback LTDA');
        $response->assertJsonPath('company.street', 'Rua das Flores');
        $response->assertJsonPath('company.phone', '2133334444');
        $response->assertJsonPath('company.email', 'financeiro@fallback.com.br');
    }

    public function test_consulta_retorna_not_found_quando_nenhuma_fonte_encontra(): void
    {
        Http::fake([
            'https://brasilapi.com.br/*' => Http::response(['message' => 'CNPJ não encontrado'], 404),
            'https://receitaws.com.br/*' => Http::response(['status' => 'ERROR', 'message' => 'CNPJ rejeitado'], 200),
        ]);

        $response = $this->getJson('/registrar/cnpj/'.self::VALID_CNPJ);

        $response->assertOk();
        $response->assertExactJson(['status' => 'not_found']);
    }

    public function test_consulta_retorna_not_found_quando_so_a_receitaws_rejeita(): void
    {
        Http::fake([
            'https://brasilapi.com.br/*' => Http::response(null, 500),
            'https://receitaws.com.br/*' => Http::response(['status' => 'ERROR', 'message' => 'CNPJ rejeitado'], 200),
        ]);

        $response = $this->getJson('/registrar/cnpj/'.self::VALID_CNPJ);

        $response->assertOk();
        $response->assertExactJson(['status' => 'not_found']);
    }

    public function test_consulta_retorna_unavailable_quando_apis_estao_indisponiveis(): void
    {
        Http::fake([
            'https://brasilapi.com.br/*' => Http::response(null, 500),
            'https://receitaws.com.br/*' => Http::response(null, 503),
        ]);

        $response = $this->getJson('/registrar/cnpj/'.self::VALID_CNPJ);

        $response->assertOk();
        $response->assertExactJson(['status' => 'unavailable']);
    }

    public function test_consulta_rejeita_cnpj_invalido_sem_chamar_apis(): void
    {
        Http::fake();

        $response = $this->getJson('/registrar/cnpj/12345678000191');

        $response->assertUnprocessable();
        $response->assertJsonPath('status', 'invalid');
        $response->assertJsonPath('message', 'O CNPJ informado é inválido.');

        Http::assertNothingSent();
    }
}
