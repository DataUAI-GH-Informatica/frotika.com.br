<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PlatformBackupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('backup.backup.name', 'frotika-test');
        config()->set('backup.backup.destination.disks', ['backups']);

        Storage::fake('backups');
    }

    public function test_admin_da_plataforma_acessa_painel_de_backups(): void
    {
        $admin = $this->createPlatformAdmin();

        Storage::disk('backups')->put('frotika-test/db-backup.zip', 'conteudo');

        $response = $this
            ->actingAs($admin)
            ->get(route('platform.backups.index'));

        $response->assertOk();
        $response->assertSee('Backups automatizados');
        $response->assertSee('db-backup.zip');
    }

    public function test_usuario_comum_nao_acessa_painel_de_backups(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'is_platform_admin' => false,
            'email_verified_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('platform.backups.index'));

        $response->assertForbidden();
    }

    public function test_admin_executa_backup_de_banco_pelo_painel(): void
    {
        $admin = $this->createPlatformAdmin();

        Artisan::shouldReceive('call')
            ->once()
            ->with('backup:run', ['--only-db' => true])
            ->andReturn(0);

        $response = $this
            ->actingAs($admin)
            ->post(route('platform.backups.run-db'));

        $response->assertRedirect(route('platform.backups.index'));
        $response->assertSessionHas('status', 'Backup de banco executado com sucesso.');
    }

    public function test_admin_executa_backup_completo_pelo_painel(): void
    {
        $admin = $this->createPlatformAdmin();

        Artisan::shouldReceive('call')
            ->once()
            ->with('backup:run')
            ->andReturn(0);

        $response = $this
            ->actingAs($admin)
            ->post(route('platform.backups.run-full'));

        $response->assertRedirect(route('platform.backups.index'));
        $response->assertSessionHas('status', 'Backup completo executado com sucesso.');
    }

    public function test_admin_executa_limpeza_de_backups_antigos_pelo_painel(): void
    {
        $admin = $this->createPlatformAdmin();

        Artisan::shouldReceive('call')
            ->once()
            ->with('backup:clean')
            ->andReturn(0);

        $response = $this
            ->actingAs($admin)
            ->post(route('platform.backups.clean'));

        $response->assertRedirect(route('platform.backups.index'));
        $response->assertSessionHas('status', 'Limpeza de backups executada com sucesso.');
    }

    public function test_admin_executa_monitoramento_de_saude_pelo_painel(): void
    {
        $admin = $this->createPlatformAdmin();

        Artisan::shouldReceive('call')
            ->once()
            ->with('backup:monitor')
            ->andReturn(0);

        $response = $this
            ->actingAs($admin)
            ->post(route('platform.backups.monitor'));

        $response->assertRedirect(route('platform.backups.index'));
        $response->assertSessionHas('status', 'Monitoramento de backups executado com sucesso.');
        $this->assertTrue(Cache::has('platform.backups.last_monitor_run_at'));
    }

    public function test_admin_baixa_arquivo_de_backup(): void
    {
        $admin = $this->createPlatformAdmin();

        Storage::disk('backups')->put('frotika-test/db-backup.zip', 'conteudo');

        $response = $this
            ->actingAs($admin)
            ->get(route('platform.backups.download', ['file' => 'db-backup.zip']));

        $response->assertOk();
        $response->assertDownload('db-backup.zip');
    }

    public function test_admin_exclui_arquivo_de_backup(): void
    {
        $admin = $this->createPlatformAdmin();

        Storage::disk('backups')->put('frotika-test/db-backup.zip', 'conteudo');

        $response = $this
            ->actingAs($admin)
            ->delete(route('platform.backups.destroy'), [
                'file' => 'db-backup.zip',
            ]);

        $response->assertRedirect(route('platform.backups.index'));
        $response->assertSessionHas('status', 'Arquivo de backup excluido com sucesso.');

        $this->assertFalse(Storage::disk('backups')->exists('frotika-test/db-backup.zip'));
    }

    private function createPlatformAdmin(): User
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'is_platform_admin' => true,
            'email_verified_at' => now(),
        ]);

        return $admin;
    }
}
