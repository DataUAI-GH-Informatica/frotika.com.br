{{--
    Chip de placa (padrão Mercosul). O átomo de identidade: todo veículo aparece
    assim, nunca como texto puro. Tarja superior brand-900 cheia no trator,
    esmaecida (opacity-40) no reboque — a diferença de 2px que informa.
--}}

@props(['plate', 'type' => null])

@php
    $plateValue = strtoupper((string) $plate);
    $isTrailer = in_array($type, ['semi_trailer', 'trailer'], true);
@endphp

<span
    class="inline-flex select-none flex-col overflow-hidden rounded-md border border-slate-800 bg-white align-middle no-select">
    <span @class(['h-[3px] w-full bg-brand-900', 'opacity-40' => $isTrailer])></span>
    <span class="px-1.5 py-0.5 font-mono text-xs font-medium uppercase tracking-widest text-slate-900">{{ $plateValue }}</span>
</span>
