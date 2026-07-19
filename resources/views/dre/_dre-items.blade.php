@foreach ($catsByGroup->get($group, collect()) as $cat)
    <tr id="line-cat-{{ $cat['category_id'] }}" data-dre-line
        class="h-9 cursor-pointer border-b border-slate-50 hover:bg-slate-50"
        onclick="dreSelect('cat-{{ $cat['category_id'] }}')">
        <td class="px-3 pl-6 text-slate-700">{{ $cat['name'] }}</td>
        <td class="px-3 text-right font-mono tabular {{ $cellTone($cat['amount_cents']) }}">{{ Format::money($cat['amount_cents']) }}</td>
        <td class="px-3 text-right font-mono tabular text-slate-400">{{ $fmtPct($cat['amount_cents']) }}</td>
        <td class="px-3 text-right font-mono tabular text-slate-400">{{ $fmtPerKm($cat['amount_cents']) }}</td>
    </tr>
@endforeach
