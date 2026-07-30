<?php

declare(strict_types=1);

namespace Tests\Feature\Attachments;

use App\Domain\Attachments\Models\Attachment;
use App\Domain\Finance\Actions\SeedDefaultFinancialCategories;
use App\Domain\Finance\Models\BankAccount;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fuelings\Models\Fueling;
use App\Domain\Tenancy\Models\Company;
use App\Domain\Tenancy\Models\Group;
use App\Domain\Trips\Models\CteDocument;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AttachmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_cupom_enviado_junto_com_o_abastecimento_fica_guardado(): void
    {
        [$owner, $company, $vehicle] = $this->scenario(1);

        $this->actingAs($owner)->post(route('fuelings.store'), [
            'vehicle_id' => $vehicle->getKey(),
            'fueled_at' => '2026-07-01T08:00',
            'odometer' => 100000,
            'product' => 'diesel_s10',
            'tank' => 'main',
            'liters' => '100,000',
            'total' => '600,00',
            'payment_method' => 'cash',
            'attachments' => [
                UploadedFile::fake()->create('cupom-posto.pdf', 120, 'application/pdf'),
            ],
        ])->assertRedirect();

        $fueling = app(TenantContext::class)->runFor($company, fn () => Fueling::query()->firstOrFail());

        $attachment = $this->attachmentOf($company);

        $this->assertSame(Fueling::class, $attachment->getAttribute('attachable_type'));
        $this->assertSame($fueling->getKey(), (int) $attachment->getAttribute('attachable_id'));
        $this->assertSame('cupom-posto.pdf', $attachment->original_name);
        $this->assertSame((int) $owner->getKey(), (int) $attachment->getAttribute('uploaded_by'));

        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_anexo_adicionado_na_tela_do_abastecimento(): void
    {
        [$owner, $company] = $this->scenario(2);
        $fueling = $this->fueling($company);

        $this->actingAs($owner)->post(route('attachments.store', [
            'owner' => 'abastecimentos',
            'id' => $fueling->getKey(),
        ]), [
            'attachments' => [UploadedFile::fake()->image('painel.jpg', 40, 40)],
        ])->assertRedirect();

        $this->assertDatabaseHas('attachments', [
            'company_id' => $company->getKey(),
            'attachable_type' => Fueling::class,
            'attachable_id' => $fueling->getKey(),
            'original_name' => 'painel.jpg',
        ]);
    }

    public function test_anexo_em_cte(): void
    {
        [$owner, $company] = $this->scenario(3);
        $cte = $this->cteDocument($company, $owner);

        $this->actingAs($owner)->post(route('attachments.store', [
            'owner' => 'ct-e',
            'id' => $cte->getKey(),
        ]), [
            'attachments' => [UploadedFile::fake()->create('canhoto.pdf', 90, 'application/pdf')],
        ])->assertRedirect();

        $this->assertDatabaseHas('attachments', [
            'company_id' => $company->getKey(),
            'attachable_type' => CteDocument::class,
            'attachable_id' => $cte->getKey(),
            'original_name' => 'canhoto.pdf',
        ]);
    }

    public function test_extensao_fora_da_lista_e_recusada(): void
    {
        [$owner, $company] = $this->scenario(4);
        $fueling = $this->fueling($company);

        $this->actingAs($owner)->post(route('attachments.store', [
            'owner' => 'abastecimentos',
            'id' => $fueling->getKey(),
        ]), [
            'attachments' => [UploadedFile::fake()->create('instalador.exe', 10)],
        ])->assertSessionHasErrors('attachments.0');

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_arquivo_acima_do_limite_e_recusado(): void
    {
        [$owner, $company] = $this->scenario(5);
        $fueling = $this->fueling($company);

        $oversized = (int) config('attachments.max_size_kb') + 1;

        $this->actingAs($owner)->post(route('attachments.store', [
            'owner' => 'abastecimentos',
            'id' => $fueling->getKey(),
        ]), [
            'attachments' => [UploadedFile::fake()->create('nota-gigante.pdf', $oversized, 'application/pdf')],
        ])->assertSessionHasErrors('attachments.0');

        $this->assertDatabaseCount('attachments', 0);
    }

    public function test_tipo_de_dono_desconhecido_da_404(): void
    {
        [$owner, $company] = $this->scenario(6);
        $fueling = $this->fueling($company);

        $this->actingAs($owner)->post(route('attachments.store', [
            'owner' => 'manutencoes',
            'id' => $fueling->getKey(),
        ]), [
            'attachments' => [UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf')],
        ])->assertNotFound();
    }

    public function test_download_devolve_o_arquivo_com_o_nome_original(): void
    {
        [$owner, $company] = $this->scenario(7);
        $fueling = $this->fueling($company);

        $this->actingAs($owner)->post(route('attachments.store', [
            'owner' => 'abastecimentos',
            'id' => $fueling->getKey(),
        ]), [
            'attachments' => [UploadedFile::fake()->create('nota-fiscal-8821.pdf', 30, 'application/pdf')],
        ])->assertRedirect();

        $attachment = $this->attachmentOf($company);

        $this->actingAs($owner)
            ->get(route('attachments.download', ['attachment' => $attachment->getKey()]))
            ->assertOk()
            ->assertDownload('nota-fiscal-8821.pdf');
    }

    public function test_membro_de_outro_grupo_nao_baixa_anexo(): void
    {
        [$owner, $company] = $this->scenario(8);
        $fueling = $this->fueling($company);

        $this->actingAs($owner)->post(route('attachments.store', [
            'owner' => 'abastecimentos',
            'id' => $fueling->getKey(),
        ]), [
            'attachments' => [UploadedFile::fake()->create('nota.pdf', 30, 'application/pdf')],
        ])->assertRedirect();

        $attachment = $this->attachmentOf($company);

        [$intruder] = $this->scenario(9);

        $this->actingAs($intruder)
            ->get(route('attachments.download', ['attachment' => $attachment->getKey()]))
            ->assertNotFound();

        $this->actingAs($intruder)
            ->delete(route('attachments.destroy', ['attachment' => $attachment->getKey()]))
            ->assertNotFound();
    }

    public function test_excluir_anexo_apaga_o_arquivo_do_disco(): void
    {
        [$owner, $company] = $this->scenario(10);
        $fueling = $this->fueling($company);

        $this->actingAs($owner)->post(route('attachments.store', [
            'owner' => 'abastecimentos',
            'id' => $fueling->getKey(),
        ]), [
            'attachments' => [UploadedFile::fake()->create('nota.pdf', 30, 'application/pdf')],
        ])->assertRedirect();

        $attachment = $this->attachmentOf($company);
        $path = $attachment->path;

        $this->actingAs($owner)
            ->delete(route('attachments.destroy', ['attachment' => $attachment->getKey()]))
            ->assertRedirect();

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->getKey()]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_anexo_fica_isolado_por_grupo_no_caminho_do_disco(): void
    {
        [$owner, $company] = $this->scenario(11);
        $fueling = $this->fueling($company);

        $this->actingAs($owner)->post(route('attachments.store', [
            'owner' => 'abastecimentos',
            'id' => $fueling->getKey(),
        ]), [
            'attachments' => [UploadedFile::fake()->create('nota.pdf', 30, 'application/pdf')],
        ])->assertRedirect();

        $group = Group::query()->findOrFail($company->getAttribute('group_id'));
        $attachment = $this->attachmentOf($company);

        $this->assertStringStartsWith(
            sprintf('grupos/%s/anexos/abastecimentos/%d/', $group->getAttribute('uuid'), $fueling->getKey()),
            $attachment->path,
        );
    }

    public function test_tela_do_abastecimento_lista_o_anexo_com_tamanho(): void
    {
        [$owner, $company] = $this->scenario(12);
        $fueling = $this->fueling($company);

        $this->actingAs($owner)->post(route('attachments.store', [
            'owner' => 'abastecimentos',
            'id' => $fueling->getKey(),
        ]), [
            'attachments' => [UploadedFile::fake()->create('cupom-ipiranga.pdf', 240, 'application/pdf')],
        ])->assertRedirect();

        $this->actingAs($owner)
            ->get(route('fuelings.show', ['fueling' => $fueling->getKey()]))
            ->assertOk()
            ->assertSee('cupom-ipiranga.pdf')
            ->assertSee('240 KB');
    }

    public function test_tela_do_cte_lista_o_anexo(): void
    {
        [$owner, $company] = $this->scenario(13);
        $cte = $this->cteDocument($company, $owner);

        $this->actingAs($owner)->post(route('attachments.store', [
            'owner' => 'ct-e',
            'id' => $cte->getKey(),
        ]), [
            'attachments' => [UploadedFile::fake()->create('canhoto-assinado.pdf', 60, 'application/pdf')],
        ])->assertRedirect();

        $this->actingAs($owner)
            ->get(route('cte.show', ['cte' => $cte->getKey()]))
            ->assertOk()
            ->assertSee('canhoto-assinado.pdf');
    }

    private function attachmentOf(Company $company): Attachment
    {
        return app(TenantContext::class)->runFor(
            $company,
            fn (): Attachment => Attachment::query()->latest('id')->firstOrFail(),
        );
    }

    private function fueling(Company $company): Fueling
    {
        return app(TenantContext::class)->runFor($company, fn (): Fueling => Fueling::query()->create([
            'vehicle_id' => Vehicle::query()->firstOrFail()->getKey(),
            'fueled_at' => '2026-07-01 08:00:00',
            'odometer' => 100000,
            'product' => 'diesel_s10',
            'tank' => 'main',
            'liters' => 100,
            'price_per_liter' => 6,
            'total_cents' => 60000,
            'full_tank' => true,
            'payment_method' => 'cash',
            'created_by' => null,
        ]));
    }

    private function cteDocument(Company $company, User $owner): CteDocument
    {
        return app(TenantContext::class)->runFor($company, fn (): CteDocument => CteDocument::query()->create([
            'access_key' => str_pad((string) $company->getKey(), 44, '3', STR_PAD_LEFT),
            'number' => 1001,
            'series' => 1,
            'cte_type' => 'normal',
            'service_type' => 'normal',
            'issued_at' => '2026-07-01 10:00:00',
            'total_value_cents' => 350000,
            'receivable_value_cents' => 350000,
            'icms_value_cents' => 0,
            'applied_share_percent' => 100,
            'status' => 'authorized',
            'imported_by' => $owner->getKey(),
            'imported_at' => now(),
        ]));
    }

    /**
     * @return array{0: User, 1: Company, 2: Vehicle}
     */
    private function scenario(int $seed): array
    {
        $owner = User::factory()->create(['email' => 'anexos-'.$seed.'@example.com']);

        $group = Group::query()->create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Grupo Anexos '.$seed,
            'type' => 'customer',
            'owner_user_id' => $owner->getKey(),
            'status' => 'active',
        ]);

        $company = Company::query()->create([
            'group_id' => $group->getKey(),
            'uuid' => Str::uuid()->toString(),
            'cnpj' => '55443322'.str_pad((string) $seed, 6, '0', STR_PAD_LEFT),
            'legal_name' => 'Anexos Empresa '.$seed.' LTDA',
            'trade_name' => 'Anexos Empresa '.$seed,
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
                'plate' => 'ANX'.str_pad((string) $seed, 4, '0', STR_PAD_LEFT),
                'type' => 'tractor', 'status' => 'active', 'ownership' => 'own',
                'odometer_initial' => 0, 'odometer_current' => 0,
            ]);
        });

        return [$owner, $company, $vehicle];
    }
}
