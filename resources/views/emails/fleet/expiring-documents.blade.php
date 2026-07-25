@php
    /** @var \App\Models\User $user */
    /** @var \App\Domain\Tenancy\Models\Company $company */
    /** @var array<int, array<string, mixed>> $items */
    /** @var string $title */

    $name = trim((string) ($user->name ?? ''));
    $firstName = $name !== '' ? explode(' ', $name)[0] : null;
    $companyName = (string) ($company->getAttribute('trade_name') ?: $company->getAttribute('legal_name'));
@endphp

<x-mail.layout heading="{{ $title }}" preheader="Há documentos da frota vencidos ou próximos do vencimento em {{ $companyName }}.">

    <p
        style="margin:0 0 16px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; line-height:1.6; color:#475569;">
        Olá{{ $firstName ? ', ' . $firstName : '' }}! Encontramos documentos da frota com vencimento próximo na empresa
        <strong style="color:#1a2536;">{{ $companyName }}</strong>.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
        style="border-collapse:collapse; margin:0 0 16px;">
        <thead>
            <tr>
                <th align="left"
                    style="padding:8px 10px; border:1px solid #e2e8f0; background:#f8fafc; font-size:12px; text-transform:uppercase; color:#64748b;">
                    Documento</th>
                <th align="left"
                    style="padding:8px 10px; border:1px solid #e2e8f0; background:#f8fafc; font-size:12px; text-transform:uppercase; color:#64748b;">
                    Referência</th>
                <th align="left"
                    style="padding:8px 10px; border:1px solid #e2e8f0; background:#f8fafc; font-size:12px; text-transform:uppercase; color:#64748b;">
                    Vencimento</th>
                <th align="left"
                    style="padding:8px 10px; border:1px solid #e2e8f0; background:#f8fafc; font-size:12px; text-transform:uppercase; color:#64748b;">
                    Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td style="padding:8px 10px; border:1px solid #e2e8f0; font-size:13px; color:#0f172a;">{{ $item['label'] }}</td>
                    <td style="padding:8px 10px; border:1px solid #e2e8f0; font-size:13px; color:#334155;">{{ $item['reference'] }}</td>
                    <td style="padding:8px 10px; border:1px solid #e2e8f0; font-size:13px; color:#0f172a;">{{ \App\Support\Format::date((string) $item['due_at']) }}</td>
                    <td style="padding:8px 10px; border:1px solid #e2e8f0; font-size:13px; color:#334155;">
                        @if (($item['alert'] ?? null) === 'expired')
                            Vencido
                        @else
                            Vence em {{ $item['days_to_expire'] }}d
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <x-mail.button :url="route('vehicles.index')">Abrir frota</x-mail.button>

    <p
        style="margin:10px 0 0; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; line-height:1.6; color:#64748b;">
        Confira os vencimentos e atualize o cadastro para evitar bloqueios operacionais.
    </p>
</x-mail.layout>
