@php
    /** @var \App\Models\User $user */
    $name = trim((string) ($user->name ?? ''));
    $firstName = $name !== '' ? explode(' ', $name)[0] : null;
    $expire = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);
@endphp

<x-mail.layout heading="Redefinir sua senha"
    preheader="Recebemos um pedido para redefinir a senha da sua conta Frotika.">

    <p style="margin:0 0 16px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; line-height:1.6; color:#475569;">
        Olá{{ $firstName ? ', '.$firstName : '' }}! Recebemos um pedido para redefinir a senha da sua conta.
        Clique no botão abaixo para escolher uma nova senha.
    </p>

    <x-mail.button :url="$url">Redefinir senha</x-mail.button>

    <p style="margin:0 0 8px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; line-height:1.6; color:#64748b;">
        Este link expira em {{ $expire }} minutos. Se você não pediu a troca de senha, ignore este e-mail —
        sua senha atual continua valendo.
    </p>

    <p style="margin:16px 0 0; padding-top:16px; border-top:1px solid #e2e8f0; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:12px; line-height:1.6; color:#94a3b8;">
        Se o botão não funcionar, copie e cole este endereço no navegador:<br />
        <a href="{{ $url }}" target="_blank" rel="noopener" style="color:#283850; word-break:break-all;">{{ $url }}</a>
    </p>
</x-mail.layout>
