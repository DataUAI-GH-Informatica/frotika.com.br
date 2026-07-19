<tr class="border-b border-slate-100 bg-slate-50/60">
    <td class="px-3 py-1.5 text-2xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</td>
    <td class="px-3 py-1.5 text-right font-mono tabular {{ $cellTone($cents) }}">{{ Format::money($cents) }}</td>
    <td class="px-3 py-1.5 text-right font-mono tabular text-slate-400">{{ $fmtPct($cents) }}</td>
    <td class="px-3 py-1.5 text-right font-mono tabular text-slate-400">{{ $fmtPerKm($cents) }}</td>
</tr>
