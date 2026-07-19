<tr id="line-{{ $key }}" data-dre-line class="h-9 cursor-pointer border-b border-slate-100 hover:bg-slate-50"
    onclick="dreSelect('{{ $key }}')">
    <td class="px-3 text-slate-700">{{ $label }}</td>
    <td class="px-3 text-right font-mono tabular {{ $cellTone($cents) }}">{{ Format::money($cents) }}</td>
    <td class="px-3 text-right font-mono tabular text-slate-400">{{ $fmtPct($cents) }}</td>
    <td class="px-3 text-right font-mono tabular text-slate-400">{{ $fmtPerKm($cents) }}</td>
</tr>
