@props([
    'heading' => null,
    'preheader' => null,
])

@php
    // E-mail exige HTML table-based com estilo inline: clientes de e-mail não
    // rodam CSS externo nem Tailwind. As cores são as da marca (navy #1a2536,
    // âmbar #fdb80f) — aqui o hex é inevitável e correto, não é UI da app.
    $icon = asset('icone-frotika.png');
    $year = date('Y');
@endphp
<!DOCTYPE html>
<html lang="pt-BR" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>{{ $heading ?? 'Frotika' }}</title>
</head>
<body style="margin:0; padding:0; width:100%; background-color:#f2f5f9;">
    @if ($preheader)
        <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:#f2f5f9; font-size:1px; line-height:1px;">
            {{ $preheader }}
        </div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
        style="background-color:#f2f5f9;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0"
                    style="width:560px; max-width:560px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">

                    {{-- Filete de acento: o único elemento chamativo --}}
                    <tr>
                        <td style="height:3px; background-color:#fdb80f; border-radius:8px 8px 0 0; line-height:3px; font-size:3px;">&nbsp;</td>
                    </tr>

                    {{-- Cabeçalho navy com a marca --}}
                    <tr>
                        <td style="background-color:#1a2536; padding:20px 28px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="background-color:#ffffff; border-radius:8px; padding:6px; width:36px; height:36px;" align="center" valign="middle">
                                        <img src="{{ $icon }}" width="28" height="28" alt="Frotika"
                                            style="display:block; width:28px; height:28px;" />
                                    </td>
                                    <td style="padding-left:12px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:18px; font-weight:700; color:#ffffff; letter-spacing:0.2px;">
                                        Frotika
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Corpo --}}
                    <tr>
                        <td style="background-color:#ffffff; border-left:1px solid #e2e8f0; border-right:1px solid #e2e8f0; padding:32px 28px;">
                            @if ($heading)
                                <h1 style="margin:0 0 16px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:22px; line-height:1.3; font-weight:700; color:#1a2536;">
                                    {{ $heading }}
                                </h1>
                            @endif

                            {{ $slot }}
                        </td>
                    </tr>

                    {{-- Rodapé --}}
                    <tr>
                        <td style="background-color:#ffffff; border:1px solid #e2e8f0; border-top:0; border-radius:0 0 8px 8px; padding:20px 28px;">
                            <p style="margin:0; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:12px; line-height:1.6; color:#94a3b8;">
                                <strong style="color:#64748b;">Frotika</strong> — Gestão inteligente para sua frota.<br />
                                Este é um e-mail automático; não é preciso responder.
                            </p>
                            <p style="margin:12px 0 0; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:11px; color:#cbd5e1;">
                                &copy; {{ $year }} Frotika
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
