<?php

declare(strict_types=1);

namespace Tests\Feature\Fuelings;

use App\Domain\Fuelings\Actions\ResolveFuelingStationForImport;
use App\Domain\Fuelings\Data\FuelingStationImportData;
use App\Domain\Partners\Enums\BusinessPartnerDocumentType;
use App\Domain\Partners\Enums\BusinessPartnerKind;
use App\Domain\Partners\Models\BusinessPartner;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ResolveFuelingStationForImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_cadastra_posto_novo_a_partir_do_cnpj(): void
    {
        $company = $this->company(1);

        $station = $this->resolve($company, new FuelingStationImportData(
            document: '11222333000181',
            legalName: 'Auto Posto Rodovia LTDA',
            tradeName: 'Posto Rodovia',
            city: 'Uberlândia',
            state: 'MG',
        ));

        $this->assertNotNull($station);
        $this->assertSame(BusinessPartnerKind::GasStation, $station->kind);
        $this->assertSame('11222333000181', $station->getAttribute('document'));
        $this->assertSame(BusinessPartnerDocumentType::Cnpj, $station->document_type);
        $this->assertSame((int) $company->getKey(), (int) $station->getAttribute('company_id'));
    }

    public function test_cnpj_sem_nome_cadastra_com_rotulo_a_partir_do_documento(): void
    {
        $company = $this->company(2);

        $station = $this->resolve($company, new FuelingStationImportData(document: '11222333000181'));

        $this->assertNotNull($station);
        $this->assertSame('Posto 11.222.333/0001-81', $station->getAttribute('legal_name'));
    }

    public function test_reaproveita_por_cnpj_enriquecendo_sem_sobrescrever(): void
    {
        $company = $this->company(3);

        $existing = $this->create($company, [
            'document' => '11222333000181',
            'document_type' => 'cnpj',
            'legal_name' => 'Razão Social Original LTDA',
            'trade_name' => 'Fantasia Original',
            'kind' => BusinessPartnerKind::GasStation->value,
            'active' => true,
        ]);

        $station = $this->resolve($company, new FuelingStationImportData(
            document: '11.222.333/0001-81',
            legalName: 'Nome Que Veio Na Planilha LTDA',
            tradeName: 'Fantasia Da Planilha',
            city: 'Uberlândia',
            state: 'MG',
        ));

        $this->assertSame((int) $existing->getKey(), (int) $station?->getKey());
        $this->assertDatabaseCount('business_partners', 1);

        // Preenche só o que estava vazio; o que já tinha valor fica como estava.
        $this->assertDatabaseHas('business_partners', [
            'id' => $existing->getKey(),
            'legal_name' => 'Razão Social Original LTDA',
            'trade_name' => 'Fantasia Original',
            'city' => 'Uberlândia',
            'state' => 'MG',
        ]);
    }

    public function test_promove_kind_generico_para_posto(): void
    {
        $company = $this->company(4);

        $existing = $this->create($company, [
            'document' => '11222333000181',
            'document_type' => 'cnpj',
            'legal_name' => 'Parceiro Sem Categoria LTDA',
            'kind' => BusinessPartnerKind::Other->value,
            'active' => true,
        ]);

        $this->resolve($company, new FuelingStationImportData(document: '11222333000181'));

        $this->assertDatabaseHas('business_partners', [
            'id' => $existing->getKey(),
            'kind' => BusinessPartnerKind::GasStation->value,
        ]);
    }

    public function test_nao_rebaixa_kind_ja_definido(): void
    {
        $company = $this->company(5);

        $existing = $this->create($company, [
            'document' => '11222333000181',
            'document_type' => 'cnpj',
            'legal_name' => 'Oficina Que Tambem Vende Diesel LTDA',
            'kind' => BusinessPartnerKind::Workshop->value,
            'active' => true,
        ]);

        $this->resolve($company, new FuelingStationImportData(document: '11222333000181'));

        $this->assertDatabaseHas('business_partners', [
            'id' => $existing->getKey(),
            'kind' => BusinessPartnerKind::Workshop->value,
        ]);
    }

    public function test_sem_cnpj_casa_pelo_nome_e_cidade(): void
    {
        $company = $this->company(6);

        $existing = $this->create($company, [
            'legal_name' => 'Auto Posto Rodovia LTDA',
            'kind' => BusinessPartnerKind::GasStation->value,
            'city' => 'Uberlândia',
            'state' => 'MG',
            'active' => true,
        ]);

        $station = $this->resolve($company, new FuelingStationImportData(
            legalName: 'Auto Posto Rodovia LTDA',
            city: 'Uberlândia',
            state: 'MG',
        ));

        $this->assertSame((int) $existing->getKey(), (int) $station?->getKey());
        $this->assertDatabaseCount('business_partners', 1);
    }

    public function test_sem_cnpj_cria_outro_quando_a_cidade_contradiz(): void
    {
        $company = $this->company(7);

        $this->create($company, [
            'legal_name' => 'Auto Posto Rodovia LTDA',
            'kind' => BusinessPartnerKind::GasStation->value,
            'city' => 'Uberlândia',
            'state' => 'MG',
            'active' => true,
        ]);

        $this->resolve($company, new FuelingStationImportData(
            legalName: 'Auto Posto Rodovia LTDA',
            city: 'Ribeirão Preto',
            state: 'SP',
        ));

        $this->assertDatabaseCount('business_partners', 2);
    }

    public function test_so_cidade_e_uf_nao_cadastra_posto(): void
    {
        $company = $this->company(8);

        $station = $this->resolve($company, new FuelingStationImportData(city: 'Uberlândia', state: 'MG'));

        $this->assertNull($station);
        $this->assertDatabaseCount('business_partners', 0);
    }

    public function test_planilha_sem_posto_nao_cadastra_nada(): void
    {
        $company = $this->company(9);

        $this->assertNull($this->resolve($company, new FuelingStationImportData));
        $this->assertDatabaseCount('business_partners', 0);
    }

    public function test_posto_de_outra_empresa_nao_e_reaproveitado(): void
    {
        $other = $this->company(10);
        $company = $this->company(11);

        $this->create($other, [
            'document' => '11222333000181',
            'document_type' => 'cnpj',
            'legal_name' => 'Auto Posto Rodovia LTDA',
            'kind' => BusinessPartnerKind::GasStation->value,
            'active' => true,
        ]);

        $station = $this->resolve($company, new FuelingStationImportData(
            document: '11222333000181',
            legalName: 'Auto Posto Rodovia LTDA',
        ));

        $this->assertNotNull($station);
        $this->assertSame((int) $company->getKey(), (int) $station->getAttribute('company_id'));
        $this->assertDatabaseCount('business_partners', 2);
    }

    private function resolve(Company $company, FuelingStationImportData $data): ?BusinessPartner
    {
        return app(ResolveFuelingStationForImport::class)->execute($company, $data);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function create(Company $company, array $attributes): BusinessPartner
    {
        return app(TenantContext::class)->runFor(
            $company,
            fn (): BusinessPartner => BusinessPartner::query()->create($attributes),
        );
    }

    private function company(int $seed): Company
    {
        $owner = User::factory()->create(['email' => 'station-import-'.$seed.'@example.com']);

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo Posto '.$seed,
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        return Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '55443322'.str_pad((string) $seed, 6, '0', STR_PAD_LEFT),
            'legal_name' => 'Posto Empresa '.$seed.' LTDA',
            'trade_name' => 'Posto Empresa '.$seed,
            'tax_regime' => 'simples',
        ]);
    }
}
