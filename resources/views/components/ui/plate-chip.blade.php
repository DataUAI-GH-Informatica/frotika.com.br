@props([
    'plate',
    'type' => null,
])

@php
    $plateValue = strtoupper((string) $plate);
    $isTrailer = in_array($type, ['semi_trailer', 'trailer'], true);
    $stripeClasses = $isTrailer ? 'bg-brand-900 border-t border-dashed border-brand-300' : 'bg-brand-900';
@endphp

<span class="inline-flex flex-col overflow-hidden rounded-md border border-slate-500 bg-white no-select">
    <span class="h-1.5 {{ $stripeClasses }}"></span>
    <span class="px-2 py-1 font-mono text-xs tracking-[0.16em] text-slate-900 tabular">{{ $plateValue }}</span>
</span>
