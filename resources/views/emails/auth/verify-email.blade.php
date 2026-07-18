@php
    /** @var \App\Models\User $user */
    $name = trim((string) ($user->name ?? ''));
    $firstName = $name !== '' ? explode(' ', $name)[0] : null;
@endphp

<x-mail.layout heading="Confirme seu e-mail"
    preheader="Falta um passo para ativar seu acesso à Frotika.">

    <p style="margin:0 0 16px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; line-height:1.6; color:#475569;">
        Olá{{ $firstName ? ', '.$firstName : '' }}! Sua conta foi criada. Para liberar o painel e começar a
        acompanhar o resultado da sua frota, confirme que este e-mail é seu.
    </p>

    <x-mail.button :url="$url">Confirmar meu e-mail</x-mail.button>

    <p style="margin:0 0 8px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; line-height:1.6; color:#64748b;">
        Se você não criou uma conta na Frotika, pode ignorar este e-mail com segurança.
    </p>

    <p style="margin:16px 0 0; padding-top:16px; border-top:1px solid #e2e8f0; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:12px; line-height:1.6; color:#94a3b8;">
        Se o botão não funcionar, copie e cole este endereço no navegador:<br />
        <a href="{{ $url }}" target="_blank" rel="noopener" style="color:#283850; word-break:break-all;">{{ $url }}</a>
    </p>
</x-mail.layout>
