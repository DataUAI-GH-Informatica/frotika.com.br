<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class BrandedEmailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_verificacao_de_email_usa_notificacao_branded(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);

        $user->sendEmailVerificationNotification();

        Notification::assertSentTo(
            $user,
            VerifyEmailNotification::class,
            fn (VerifyEmailNotification $notification): bool => $notification->toMail($user)->subject === 'Confirme seu e-mail — Frotika'
        );
    }

    public function test_redefinicao_de_senha_usa_notificacao_branded(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $user->sendPasswordResetNotification('token-de-teste');

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            fn (ResetPasswordNotification $notification): bool => $notification->toMail($user)->subject === 'Redefinir sua senha — Frotika'
        );
    }

    public function test_template_de_verificacao_renderiza_marca_e_botao(): void
    {
        $user = User::factory()->make(['name' => 'Guilherme Silva']);

        $html = view('emails.auth.verify-email', [
            'url' => 'https://frotika.test/confirmar/abc',
            'user' => $user,
        ])->render();

        $this->assertStringContainsString('Frotika', $html);
        $this->assertStringContainsString('Confirmar meu e-mail', $html);
        $this->assertStringContainsString('https://frotika.test/confirmar/abc', $html);
        $this->assertStringContainsString('Guilherme', $html);
        $this->assertStringContainsString('#1a2536', $html);
    }

    public function test_template_de_reset_renderiza_marca_e_botao(): void
    {
        $user = User::factory()->make(['name' => 'Guilherme Silva']);

        $html = view('emails.auth.reset-password', [
            'url' => 'https://frotika.test/redefinir/xyz',
            'user' => $user,
        ])->render();

        $this->assertStringContainsString('Frotika', $html);
        $this->assertStringContainsString('Redefinir senha', $html);
        $this->assertStringContainsString('https://frotika.test/redefinir/xyz', $html);
    }

    public function test_comando_de_preview_envia_todos_os_emails(): void
    {
        Notification::fake();

        $this->artisan('frotika:mail-preview --to=avaliacao@frotika.test')
            ->expectsOutputToContain('2 e-mail(s) enviados para avaliacao@frotika.test')
            ->assertSuccessful();

        Notification::assertCount(2);
    }
}
