<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Domain\Tenancy\Enums\GroupType;
use App\Domain\Tenancy\Models\Group;
use App\Models\User;
use Database\Seeders\PlatformAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlatformAdminSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_cria_conta_admin_e_grupo_plataforma(): void
    {
        $this->seed(PlatformAdminSeeder::class);

        $email = (string) config('platform.admin_email');

        $admin = User::query()->where('email', $email)->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin?->isPlatformAdmin());

        $group = Group::query()->where('type', GroupType::Platform->value)->first();
        $this->assertNotNull($group);
        $this->assertTrue($group?->isPlatform());
    }

    public function test_comando_promove_conta_existente_a_admin_da_plataforma(): void
    {
        $user = User::factory()->create([
            'email' => 'novo-admin@frotika.com.br',
            'is_platform_admin' => false,
        ]);

        $this->artisan('frotika:promote-platform-admin', ['email' => 'novo-admin@frotika.com.br'])
            ->assertExitCode(0);

        $this->assertTrue($user->fresh()->isPlatformAdmin());
    }

    public function test_comando_falha_para_email_inexistente(): void
    {
        $this->artisan('frotika:promote-platform-admin', ['email' => 'ninguem@frotika.com.br'])
            ->assertExitCode(1);
    }
}
