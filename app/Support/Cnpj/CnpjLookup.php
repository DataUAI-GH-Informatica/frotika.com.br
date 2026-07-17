<?php

declare(strict_types=1);

namespace App\Support\Cnpj;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Consulta dados de CNPJ na Receita, com duas fontes: BrasilAPI (primária) e
 * ReceitaWS (fallback). Não decide nada de negócio — só busca e normaliza.
 *
 * - Encontrou em qualquer fonte → Found com os dados.
 * - Alguma fonte respondeu "não existe" e nenhuma achou → NotFound.
 * - As duas fontes falharam (rede, indisponibilidade) → Unavailable.
 */
final class CnpjLookup
{
    private const TIMEOUT_SECONDS = 10;

    public function find(string $cnpj): CnpjLookupResult
    {
        $digits = Cnpj::digits($cnpj);

        $brasil = $this->fetchFromBrasilApi($digits);
        if ($brasil instanceof CnpjData) {
            return CnpjLookupResult::found($brasil);
        }

        $receita = $this->fetchFromReceitaWs($digits);
        if ($receita instanceof CnpjData) {
            return CnpjLookupResult::found($receita);
        }

        if ($brasil === CnpjLookupStatus::NotFound || $receita === CnpjLookupStatus::NotFound) {
            return CnpjLookupResult::notFound();
        }

        return CnpjLookupResult::unavailable();
    }

    /**
     * BrasilAPI (fonte primária).
     *
     * @return CnpjData|CnpjLookupStatus|null CnpjData em sucesso, NotFound em 404,
     *                                        null em erro (sinal para tentar o fallback).
     */
    private function fetchFromBrasilApi(string $cnpj): CnpjData|CnpjLookupStatus|null
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->get("https://brasilapi.com.br/api/cnpj/v1/{$cnpj}");

            if ($response->successful()) {
                $data = (array) $response->json();

                return new CnpjData(
                    source: 'brasilapi',
                    legalName: $this->str($data, 'razao_social'),
                    tradeName: $this->str($data, 'nome_fantasia'),
                    registrationStatus: $this->str($data, 'descricao_situacao_cadastral'),
                    openedAt: $this->str($data, 'data_inicio_atividade'),
                    legalNature: $this->str($data, 'natureza_juridica'),
                    primaryCnae: $this->str($data, 'cnae_fiscal'),
                    primaryCnaeDescription: $this->str($data, 'cnae_fiscal_descricao'),
                    street: $this->str($data, 'logradouro'),
                    number: $this->str($data, 'numero'),
                    complement: $this->str($data, 'complemento'),
                    district: $this->str($data, 'bairro'),
                    city: $this->str($data, 'municipio'),
                    state: $this->str($data, 'uf'),
                    zipCode: $this->str($data, 'cep'),
                    phone: $this->sanitizePhone($this->str($data, 'ddd_telefone_1')),
                    email: $this->str($data, 'email'),
                );
            }

            if ($response->status() === 404) {
                return CnpjLookupStatus::NotFound;
            }

            return null;
        } catch (ConnectionException) {
            return null;
        }
    }

    /**
     * ReceitaWS (fallback). Normaliza a resposta para o mesmo formato.
     *
     * @return CnpjData|CnpjLookupStatus|null CnpjData em sucesso, NotFound quando a
     *                                        Receita rejeita o CNPJ, null em erro de rede/HTTP.
     */
    private function fetchFromReceitaWs(string $cnpj): CnpjData|CnpjLookupStatus|null
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->get("https://receitaws.com.br/v1/cnpj/{$cnpj}");

            if ($response->failed()) {
                return null;
            }

            $data = (array) $response->json();

            if (($data['status'] ?? '') !== 'OK') {
                return CnpjLookupStatus::NotFound;
            }

            $primaryActivity = [];
            if (isset($data['atividade_principal']) && is_array($data['atividade_principal'])) {
                $first = $data['atividade_principal'][0] ?? null;
                $primaryActivity = is_array($first) ? $first : [];
            }

            return new CnpjData(
                source: 'receitaws',
                legalName: $this->str($data, 'nome'),
                tradeName: $this->str($data, 'fantasia'),
                registrationStatus: $this->str($data, 'situacao'),
                openedAt: $this->str($data, 'abertura'),
                legalNature: $this->str($data, 'natureza_juridica'),
                primaryCnae: $this->str($primaryActivity, 'code'),
                primaryCnaeDescription: $this->str($primaryActivity, 'text'),
                street: $this->str($data, 'logradouro'),
                number: $this->str($data, 'numero'),
                complement: $this->str($data, 'complemento'),
                district: $this->str($data, 'bairro'),
                city: $this->str($data, 'municipio'),
                state: $this->str($data, 'uf'),
                zipCode: $this->str($data, 'cep'),
                phone: $this->sanitizePhone($this->str($data, 'telefone')),
                email: $this->str($data, 'email'),
            );
        } catch (ConnectionException) {
            return null;
        }
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function str(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function sanitizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return $digits === '' ? null : $digits;
    }
}
