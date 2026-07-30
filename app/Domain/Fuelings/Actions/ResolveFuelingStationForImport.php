<?php

declare(strict_types=1);

namespace App\Domain\Fuelings\Actions;

use App\Domain\Fuelings\Data\FuelingStationImportData;
use App\Domain\Partners\Enums\BusinessPartnerDocumentType;
use App\Domain\Partners\Enums\BusinessPartnerKind;
use App\Domain\Partners\Models\BusinessPartner;
use App\Domain\Tenancy\Models\Company;
use App\Support\Cnpj\Cnpj;
use App\Support\Tenancy\TenantContext;

/**
 * Resolve o posto de uma linha da planilha, cadastrando o parceiro quando ele
 * ainda não existe. Segue a mesma política do UpsertBusinessPartner (ADR-004):
 * enriquece campo vazio, nunca sobrescreve dado editado à mão e só promove o
 * `kind` quando ele ainda é genérico.
 *
 * O posto é opcional. Quando a planilha traz apenas cidade e UF, sem CNPJ nem
 * nome, não há parceiro para criar — os dados ficam no texto livre do próprio
 * abastecimento e a linha importa normalmente.
 */
final class ResolveFuelingStationForImport
{
    public function __construct(private readonly TenantContext $tenant) {}

    public function execute(Company $company, FuelingStationImportData $data): ?BusinessPartner
    {
        if ($data->isEmpty()) {
            return null;
        }

        return $this->tenant->runFor($company, function () use ($data): ?BusinessPartner {
            $document = $data->documentDigits();

            if ($document !== null) {
                $existing = BusinessPartner::query()->where('document', $document)->first();

                if ($existing instanceof BusinessPartner) {
                    $this->enrich($existing, $data);

                    return $existing;
                }

                return $this->create($data);
            }

            if ($data->name() === null) {
                return null;
            }

            $existing = $this->findByName($data);

            if ($existing instanceof BusinessPartner) {
                $this->enrich($existing, $data);

                return $existing;
            }

            return $this->create($data);
        });
    }

    /**
     * Sem CNPJ, o melhor que dá para fazer é casar por nome e conferir que
     * cidade e UF não contradizem o cadastro. Um parceiro sem cidade preenchida
     * é considerado o mesmo posto — é o caso de quem cadastrou às pressas.
     */
    private function findByName(FuelingStationImportData $data): ?BusinessPartner
    {
        $name = (string) $data->name();

        return BusinessPartner::query()
            ->where(function ($query) use ($name): void {
                $query->where('legal_name', $name)->orWhere('trade_name', $name);
            })
            ->get()
            ->first(function (BusinessPartner $partner) use ($data): bool {
                return $this->agrees($partner->getAttribute('city'), $data->city)
                    && $this->agrees($partner->getAttribute('state'), $data->state);
            });
    }

    private function agrees(mixed $stored, ?string $incoming): bool
    {
        if ($incoming === null || $stored === null || $stored === '') {
            return true;
        }

        return mb_strtolower((string) $stored) === mb_strtolower($incoming);
    }

    private function create(FuelingStationImportData $data): BusinessPartner
    {
        $document = $data->documentDigits();

        /** @var BusinessPartner $partner */
        $partner = BusinessPartner::query()->create([
            'document' => $document,
            'document_type' => BusinessPartnerDocumentType::fromDigits($document)->value,
            'legal_name' => $data->name() ?? sprintf('Posto %s', Cnpj::format((string) $document)),
            'trade_name' => $data->tradeName,
            'kind' => BusinessPartnerKind::GasStation->value,
            'city' => $data->city,
            'state' => $data->state,
            'active' => true,
        ]);

        return $partner;
    }

    private function enrich(BusinessPartner $partner, FuelingStationImportData $data): void
    {
        $fillIfEmpty = [
            'trade_name' => $data->tradeName,
            'city' => $data->city,
            'state' => $data->state,
        ];

        foreach ($fillIfEmpty as $attribute => $value) {
            if ($value !== null && $partner->getAttribute($attribute) === null) {
                $partner->setAttribute($attribute, $value);
            }
        }

        if ($partner->kind === BusinessPartnerKind::Other) {
            $partner->setAttribute('kind', BusinessPartnerKind::GasStation->value);
        }

        if ($partner->isDirty()) {
            $partner->save();
        }
    }
}
