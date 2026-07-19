<tr class="border-t border-slate-200">
    <td class="px-3 py-2 font-display text-sm font-semibold text-slate-900">= {{ $label }}</td>
    <td class="px-3 py-2 text-right font-display text-sm font-semibold tabular {{ $cellTone($cents) }}">{{ Format::money($cents) }}</td>
    <td class="px-3 py-2 text-right font-mono tabular text-slate-500">{{ $fmtPct($cents) }}</td>
    <td class="px-3 py-2 text-right font-mono tabular text-slate-500">{{ $fmtPerKm($cents) }}</td>
</tr>
