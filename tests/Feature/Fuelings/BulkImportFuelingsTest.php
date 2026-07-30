<?php

declare(strict_types=1);

namespace Tests\Feature\Fuelings;

use App\Domain\Finance\Actions\SeedDefaultFinancialCategories;
use App\Domain\Finance\Models\BankAccount;
use App\Domain\Fleet\Models\Driver;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fuelings\Enums\FuelingImportBatchStatus;
use App\Domain\Fuelings\Enums\FuelingImportItemStatus;
use App\Domain\Fuelings\Events\FuelingBulkImportCompleted;
use App\Domain\Fuelings\Import\FuelingImportSheet;
use App\Domain\Fuelings\Import\FuelingSheetReader;
use App\Domain\Fuelings\Models\Fueling;
use App\Domain\Fuelings\Models\FuelingImportBatch;
use App\Domain\Partners\Models\BusinessPartner;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

final class BulkImportFuelingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_planilha_com_multiplas_linhas_importa_e_notifica_ao_concluir(): void
    {
        Storage::fake('local');
        Event::fake([FuelingBulkImportCompleted::class]);
        [$owner, $company, $vehicle] = $this->scenario(1);
        $plate = (string) $vehicle->getAttribute('plate');

        $response = $this->actingAs($owner)->post(route('fuelings.import.store'), [
            'sheet' => $this->upload([
                $this->row($plate, [
                    FuelingImportSheet::ODOMETER => '100000',
                    FuelingImportSheet::FULL_TANK => 'sim',
                    FuelingImportSheet::FUELED_AT => '01/07/2026 08:30',
                ]),
                $this->row($plate, [
                    FuelingImportSheet::ODOMETER => '101000',
                    FuelingImportSheet::LITERS => '100,000',
                    FuelingImportSheet::TOTAL => '600,00',
                    FuelingImportSheet::FULL_TANK => 'sim',
                    FuelingImportSheet::FUELED_AT => '05/07/2026 09:15',
                ]),
            ]),
        ]);

        $batch = $this->batch();
        $response->assertRedirect(route('fuelings.import.result', ['batch' => $batch->getAttribute('uuid')]));

        $this->assertSame(FuelingImportBatchStatus::Completed, $batch->status);
        $this->assertSame(2, $batch->total_rows);
        $this->assertSame(2, $batch->processed_rows);
        $this->assertSame(2, $batch->imported_count);
        $this->assertSame(0, $batch->ignored_count);
        $this->assertSame(0, $batch->failed_count);

        $this->assertDatabaseCount('fuelings', 2);

        // A ordem das linhas é respeitada, então o consumo sai do intervalo
        // entre os dois tanques cheios: 1000 km em 100 litros.
        $second = app(TenantContext::class)->runFor(
            $company,
            fn (): Fueling => Fueling::query()->orderByDesc('fueled_at')->firstOrFail(),
        );
        $this->assertSame('10.000', $second->km_per_liter);

        Event::assertDispatched(FuelingBulkImportCompleted::class, function (FuelingBulkImportCompleted $event) use ($owner, $batch): bool {
            return $event->userId === (int) $owner->getKey()
                && $event->uuid === (string) $batch->getAttribute('uuid')
                && $event->imported === 2
                && $event->ignored === 0
                && $event->failed === 0;
        });
    }

    public function test_importacao_gera_lancamento_financeiro_como_o_lancamento_manual(): void
    {
        Storage::fake('local');
        [$owner, $company, $vehicle] = $this->scenario(2);

        $this->actingAs($owner)->post(route('fuelings.import.store'), [
            'sheet' => $this->upload([$this->row((string) $vehicle->getAttribute('plate'))]),
        ])->assertRedirect();

        $fueling = app(TenantContext::class)->runFor($company, fn (): Fueling => Fueling::query()->firstOrFail());

        // Regra 7: o abastecimento importado passa pelo observer como qualquer
        // outro, então o lançamento financeiro existe sem a importação tocá-lo.
        $this->assertDatabaseHas('financial_entries', [
            'company_id' => $company->getKey(),
            'sourceable_type' => Fueling::class,
            'sourceable_id' => $fueling->getKey(),
            'type' => 'expense',
            'status' => 'settled',
            'amount_cents' => 120000,
        ]);

        $this->assertDatabaseHas('bank_accounts', [
            'is_default' => 1,
            'current_balance_cents' => -120000,
        ]);
    }

    public function test_linha_invalida_nao_derruba_as_demais(): void
    {
        Storage::fake('local');
        Event::fake([FuelingBulkImportCompleted::class]);
        [$owner, , $vehicle] = $this->scenario(3);

        $this->actingAs($owner)->post(route('fuelings.import.store'), [
            'sheet' => $this->upload([
                $this->row((string) $vehicle->getAttribute('plate')),
                $this->row('XXX9Z99', [FuelingImportSheet::ODOMETER => '120000']),
                $this->row((string) $vehicle->getAttribute('plate'), [
                    FuelingImportSheet::ODOMETER => '130000',
                    FuelingImportSheet::PRODUCT => 'querosene',
                ]),
            ]),
        ])->assertRedirect();

        $batch = $this->batch();

        $this->assertSame(FuelingImportBatchStatus::Completed, $batch->status);
        $this->assertSame(1, $batch->imported_count);
        $this->assertSame(2, $batch->failed_count);
        $this->assertDatabaseCount('fuelings', 1);

        $results = $this->resultsByRow($batch);

        $this->assertSame(FuelingImportItemStatus::Imported->value, $results[2]['status']);
        $this->assertSame(FuelingImportItemStatus::Failed->value, $results[3]['status']);
        $this->assertStringContainsString('XXX9Z99', (string) $results[3]['message']);
        $this->assertSame(FuelingImportItemStatus::Failed->value, $results[4]['status']);
        $this->assertStringContainsString('diesel_s10', (string) $results[4]['message']);

        Event::assertDispatched(FuelingBulkImportCompleted::class);
    }

    public function test_odometro_menor_que_o_ultimo_falha_a_linha_sem_lancar(): void
    {
        Storage::fake('local');
        [$owner, , $vehicle] = $this->scenario(16);
        $plate = (string) $vehicle->getAttribute('plate');

        $this->actingAs($owner)->post(route('fuelings.import.store'), [
            'sheet' => $this->upload([
                $this->row($plate, [FuelingImportSheet::ODOMETER => '100000']),
                $this->row($plate, [
                    FuelingImportSheet::FUELED_AT => '05/07/2026 09:15',
                    FuelingImportSheet::ODOMETER => '90000',
                ]),
            ]),
        ])->assertRedirect();

        $batch = $this->batch();

        // A guarda de odômetro do CreateFueling vale na importação: em lote não
        // existe a confirmação de correção que a tela oferece, então a linha falha.
        $this->assertSame(1, $batch->imported_count);
        $this->assertSame(1, $batch->failed_count);
        $this->assertDatabaseCount('fuelings', 1);

        $results = $this->resultsByRow($batch);

        $this->assertSame(FuelingImportItemStatus::Failed->value, $results[3]['status']);
        $this->assertStringContainsString('menor que o último lançado', (string) $results[3]['message']);
    }

    public function test_reimportar_ignora_linha_pelo_codigo_do_abastecimento(): void
    {
        Storage::fake('local');
        [$owner, , $vehicle] = $this->scenario(4);
        $plate = (string) $vehicle->getAttribute('plate');

        $rows = [
            $this->row($plate, [FuelingImportSheet::CODE => 'AB-1', FuelingImportSheet::ODOMETER => '100000']),
            $this->row($plate, [FuelingImportSheet::CODE => 'AB-2', FuelingImportSheet::ODOMETER => '101000']),
        ];

        $this->actingAs($owner)->post(route('fuelings.import.store'), ['sheet' => $this->upload($rows)])->assertRedirect();
        $this->assertSame(2, $this->batch()->imported_count);

        // Mesmo arquivo de novo: nada duplica, tudo é ignorado.
        $this->actingAs($owner)->post(route('fuelings.import.store'), ['sheet' => $this->upload($rows)])->assertRedirect();

        $second = FuelingImportBatch::withoutGlobalScopes()->orderByDesc('id')->firstOrFail();

        $this->assertSame(0, $second->imported_count);
        $this->assertSame(2, $second->ignored_count);
        $this->assertSame(0, $second->failed_count);
        $this->assertDatabaseCount('fuelings', 2);
        $this->assertStringContainsString('AB-1', (string) $this->resultsByRow($second)[2]['message']);
    }

    public function test_reimportar_sem_codigo_ignora_pela_composicao_da_linha(): void
    {
        Storage::fake('local');
        [$owner, , $vehicle] = $this->scenario(5);
        $rows = [$this->row((string) $vehicle->getAttribute('plate'))];

        $this->actingAs($owner)->post(route('fuelings.import.store'), ['sheet' => $this->upload($rows)])->assertRedirect();
        $this->actingAs($owner)->post(route('fuelings.import.store'), ['sheet' => $this->upload($rows)])->assertRedirect();

        $second = FuelingImportBatch::withoutGlobalScopes()->orderByDesc('id')->firstOrFail();

        $this->assertSame(0, $second->imported_count);
        $this->assertSame(1, $second->ignored_count);
        $this->assertDatabaseCount('fuelings', 1);
    }

    public function test_posto_novo_e_cadastrado_pelo_cnpj_da_planilha(): void
    {
        Storage::fake('local');
        [$owner, $company, $vehicle] = $this->scenario(6);

        $this->actingAs($owner)->post(route('fuelings.import.store'), [
            'sheet' => $this->upload([
                $this->row((string) $vehicle->getAttribute('plate'), [
                    FuelingImportSheet::STATION_DOCUMENT => '11.222.333/0001-81',
                    FuelingImportSheet::STATION_LEGAL_NAME => 'Auto Posto Rodovia LTDA',
                    FuelingImportSheet::STATION_TRADE_NAME => 'Posto Rodovia',
                    FuelingImportSheet::STATION_CITY => 'Uberlândia',
                    FuelingImportSheet::STATION_STATE => 'mg',
                ]),
            ]),
        ])->assertRedirect();

        $this->assertSame(1, $this->batch()->imported_count);

        $this->assertDatabaseHas('business_partners', [
            'company_id' => $company->getKey(),
            'document' => '11222333000181',
            'document_type' => 'cnpj',
            'legal_name' => 'Auto Posto Rodovia LTDA',
            'kind' => 'gas_station',
            'state' => 'MG',
        ]);

        $station = app(TenantContext::class)->runFor(
            $company,
            fn (): BusinessPartner => BusinessPartner::query()->firstOrFail(),
        );

        $this->assertDatabaseHas('fuelings', [
            'company_id' => $company->getKey(),
            'supplier_id' => $station->getKey(),
            'station_name' => 'Posto Rodovia',
            'station_state' => 'MG',
        ]);
    }

    public function test_posto_ja_cadastrado_e_reaproveitado_sem_duplicar(): void
    {
        Storage::fake('local');
        [$owner, $company, $vehicle] = $this->scenario(7);

        $existing = app(TenantContext::class)->runFor($company, fn (): BusinessPartner => BusinessPartner::query()->create([
            'document' => '11222333000181',
            'document_type' => 'cnpj',
            'legal_name' => 'Auto Posto Rodovia LTDA',
            'kind' => 'gas_station',
            'active' => true,
        ]));

        $this->actingAs($owner)->post(route('fuelings.import.store'), [
            'sheet' => $this->upload([
                $this->row((string) $vehicle->getAttribute('plate'), [
                    FuelingImportSheet::STATION_DOCUMENT => '11222333000181',
                    FuelingImportSheet::STATION_LEGAL_NAME => 'Nome Diferente LTDA',
                    FuelingImportSheet::STATION_CITY => 'Uberlândia',
                ]),
            ]),
        ])->assertRedirect();

        $this->assertDatabaseCount('business_partners', 1);

        // Enriquece o que estava vazio e não sobrescreve a razão social existente.
        $this->assertDatabaseHas('business_partners', [
            'id' => $existing->getKey(),
            'legal_name' => 'Auto Posto Rodovia LTDA',
            'city' => 'Uberlândia',
        ]);

        $this->assertDatabaseHas('fuelings', ['supplier_id' => $existing->getKey()]);
    }

    public function test_motorista_e_vinculado_por_cpf_e_cpf_desconhecido_falha_a_linha(): void
    {
        Storage::fake('local');
        [$owner, $company, $vehicle] = $this->scenario(8);
        $plate = (string) $vehicle->getAttribute('plate');

        $driver = app(TenantContext::class)->runFor($company, fn (): Driver => Driver::query()->create([
            'name' => 'José Motorista',
            'cpf' => '52998224725',
            'status' => 'active',
        ]));

        $this->actingAs($owner)->post(route('fuelings.import.store'), [
            'sheet' => $this->upload([
                $this->row($plate, [FuelingImportSheet::DRIVER_CPF => '529.982.247-25']),
                $this->row($plate, [
                    FuelingImportSheet::FUELED_AT => '02/07/2026 08:30',
                    FuelingImportSheet::ODOMETER => '110000',
                    FuelingImportSheet::DRIVER_CPF => '111.444.777-35',
                ]),
            ]),
        ])->assertRedirect();

        $batch = $this->batch();

        $this->assertSame(1, $batch->imported_count);
        $this->assertSame(1, $batch->failed_count);
        $this->assertDatabaseHas('fuelings', ['driver_id' => $driver->getKey()]);
        $this->assertStringContainsString('111.444.777-35', (string) $this->resultsByRow($batch)[3]['message']);
    }

    public function test_empresa_nao_ve_lote_de_importacao_de_outra(): void
    {
        Storage::fake('local');
        [$owner, , $vehicle] = $this->scenario(9);

        $this->actingAs($owner)->post(route('fuelings.import.store'), [
            'sheet' => $this->upload([$this->row((string) $vehicle->getAttribute('plate'))]),
        ])->assertRedirect();

        $batch = $this->batch();
        [$intruder] = $this->scenario(10);

        $this->actingAs($intruder)
            ->get(route('fuelings.import.result', ['batch' => $batch->getAttribute('uuid')]))
            ->assertNotFound();

        // E o abastecimento importado também não aparece para a outra empresa.
        $this->actingAs($intruder)
            ->get(route('fuelings.index'))
            ->assertOk()
            ->assertDontSee((string) $vehicle->getAttribute('plate'));
    }

    public function test_recusa_arquivo_que_nao_e_xlsx(): void
    {
        Storage::fake('local');
        [$owner] = $this->scenario(11);

        $this->actingAs($owner)
            ->post(route('fuelings.import.store'), ['sheet' => UploadedFile::fake()->create('abastecimentos.csv', 10)])
            ->assertSessionHasErrors(['sheet']);

        $this->assertDatabaseCount('fueling_import_batches', 0);
    }

    public function test_recusa_planilha_sem_coluna_obrigatoria(): void
    {
        Storage::fake('local');
        [$owner, , $vehicle] = $this->scenario(12);

        $headers = array_values(array_filter(
            FuelingImportSheet::columns(),
            static fn (string $column): bool => $column !== FuelingImportSheet::ODOMETER,
        ));

        $this->actingAs($owner)
            ->post(route('fuelings.import.store'), [
                'sheet' => $this->upload([$this->row((string) $vehicle->getAttribute('plate'))], $headers),
            ])
            ->assertSessionHasErrors(['sheet']);

        $this->assertDatabaseCount('fueling_import_batches', 0);
        $this->assertDatabaseCount('fuelings', 0);
    }

    public function test_recusa_planilha_sem_nenhuma_linha_de_dados(): void
    {
        Storage::fake('local');
        [$owner] = $this->scenario(13);

        $this->actingAs($owner)
            ->post(route('fuelings.import.store'), ['sheet' => $this->upload([])])
            ->assertSessionHasErrors(['sheet']);

        $this->assertDatabaseCount('fueling_import_batches', 0);
    }

    public function test_usuario_sem_permissao_de_lancar_nao_importa(): void
    {
        Storage::fake('local');
        [, $company, $vehicle] = $this->scenario(14);
        $viewer = $this->viewerOf($company);

        $this->actingAs($viewer)->get(route('fuelings.import'))->assertForbidden();
        $this->actingAs($viewer)->get(route('fuelings.import.template'))->assertForbidden();

        $this->actingAs($viewer)
            ->post(route('fuelings.import.store'), [
                'sheet' => $this->upload([$this->row((string) $vehicle->getAttribute('plate'))]),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('fueling_import_batches', 0);
    }

    public function test_planilha_modelo_e_baixavel_e_pode_ser_reimportada(): void
    {
        Storage::fake('local');
        [$owner] = $this->scenario(15);

        $response = $this->actingAs($owner)->get(route('fuelings.import.template'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // A linha de exemplo tem que passar pelo leitor: se o modelo não for
        // importável, ninguém consegue seguir a instrução da tela.
        $path = (string) tempnam(sys_get_temp_dir(), 'frotika-template-test');
        file_put_contents($path, $response->streamedContent());

        $rows = app(FuelingSheetReader::class)->read($path);
        unlink($path);

        $this->assertCount(1, $rows);
        $this->assertSame('RTA4B56', $rows[0]->text(FuelingImportSheet::PLATE));
    }

    private function batch(): FuelingImportBatch
    {
        return FuelingImportBatch::withoutGlobalScopes()->orderByDesc('id')->firstOrFail();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resultsByRow(FuelingImportBatch $batch): array
    {
        $indexed = [];

        foreach ($batch->results ?? [] as $result) {
            $indexed[(int) $result['row']] = $result;
        }

        return $indexed;
    }

    /**
     * @param  array<string, string>  $overrides
     * @return array<string, string>
     */
    private function row(string $plate, array $overrides = []): array
    {
        return array_merge([
            FuelingImportSheet::PLATE => $plate,
            FuelingImportSheet::FUELED_AT => '01/07/2026 08:30',
            FuelingImportSheet::ODOMETER => '100000',
            FuelingImportSheet::PRODUCT => 'diesel_s10',
            FuelingImportSheet::LITERS => '200,000',
            FuelingImportSheet::TOTAL => '1.200,00',
            FuelingImportSheet::PAYMENT_METHOD => 'cash',
        ], $overrides);
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @param  list<string>|null  $headers
     */
    private function upload(array $rows, ?array $headers = null, string $name = 'abastecimentos.xlsx'): UploadedFile
    {
        $headers ??= FuelingImportSheet::columns();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $index => $header) {
            $sheet->setCellValueExplicit(Coordinate::stringFromColumnIndex($index + 1).'1', $header, DataType::TYPE_STRING);
        }

        foreach ($rows as $offset => $row) {
            foreach ($headers as $index => $header) {
                $value = $row[$header] ?? '';

                if ($value === '') {
                    continue;
                }

                $sheet->setCellValueExplicit(
                    Coordinate::stringFromColumnIndex($index + 1).($offset + 2),
                    $value,
                    DataType::TYPE_STRING,
                );
            }
        }

        $path = (string) tempnam(sys_get_temp_dir(), 'frotika-import-test');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return new UploadedFile(
            $path,
            $name,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    private function viewerOf(Company $company): User
    {
        $viewer = User::factory()->create();

        $viewer->groups()->attach($company->getAttribute('group_id'), [
            'role' => 'viewer',
            'invited_by' => null,
            'joined_at' => now(),
        ]);
        $viewer->companies()->attach($company->getKey());
        $viewer->forceFill([
            'current_group_id' => $company->getAttribute('group_id'),
            'current_company_id' => $company->getKey(),
        ])->save();

        return $viewer;
    }

    /**
     * @return array{0: User, 1: Company, 2: Vehicle}
     */
    private function scenario(int $seed): array
    {
        $owner = User::factory()->create(['email' => 'fuel-import-'.$seed.'@example.com']);

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo Import '.$seed,
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        $company = Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '99887766'.str_pad((string) $seed, 6, '0', STR_PAD_LEFT),
            'legal_name' => 'Import Empresa '.$seed.' LTDA',
            'trade_name' => 'Import Empresa '.$seed,
            'tax_regime' => 'simples',
        ]);

        $owner->groups()->attach($group->getKey(), [
            'role' => 'owner',
            'invited_by' => null,
            'joined_at' => now(),
        ]);
        $owner->companies()->attach($company->getKey());
        $owner->forceFill([
            'current_group_id' => $group->getKey(),
            'current_company_id' => $company->getKey(),
        ])->save();

        $vehicle = app(TenantContext::class)->runFor($company, function () use ($company, $seed): Vehicle {
            app(SeedDefaultFinancialCategories::class)->execute($company);

            BankAccount::query()->create([
                'name' => 'Caixa', 'type' => 'cash', 'initial_balance_cents' => 0,
                'current_balance_cents' => 0, 'is_default' => true, 'active' => true,
            ]);

            return Vehicle::query()->create([
                'company_id' => $company->getKey(),
                'plate' => 'IMP'.str_pad((string) $seed, 4, '0', STR_PAD_LEFT),
                'type' => 'tractor', 'status' => 'active', 'ownership' => 'own',
                'odometer_initial' => 0, 'odometer_current' => 0,
            ]);
        });

        return [$owner, $company, $vehicle];
    }
}
