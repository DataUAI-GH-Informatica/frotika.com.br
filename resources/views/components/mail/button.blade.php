@props([
    'url' => '#',
])

{{-- Botão "bulletproof" table-based: renderiza igual em Outlook/Gmail/Apple Mail. --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;">
    <tr>
        <td align="center" bgcolor="#1a2536" style="border-radius:6px;">
            <a href="{{ $url }}" target="_blank" rel="noopener"
                style="display:inline-block; padding:12px 26px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; font-weight:600; line-height:1; color:#ffffff; text-decoration:none; border-radius:6px;">
                {{ $slot }}
            </a>
        </td>
    </tr>
</table>
