{{--
    Card de indicador. O número é o herói: font-display, grande, alinhado à
    vírgula. Sem ícone, sem sombra, sem cor de fundo.

    Cor só quando codifica um fato (resultado negativo). Padrão é monocromático —
    tela de frota saudável não tem cor.
--}}

@props(['label', 'value', 'tone' => 'default', 'hint' => null, 'unit' => null])

@php
    $valueToneClasses =
        [
            'default' => 'text-slate-900',
            'success' => 'text-success-700',
            'danger' => 'text-danger-700',
            'info' => 'text-info-700',
            'warning' => 'text-warning-700',
        ][$tone] ?? 'text-slate-900';
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg border border-slate-200 bg-white p-4']) }}>
    <p class="text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $label }}</p>
    <p class="mt-1 font-display text-2xl font-bold tabular {{ $valueToneClasses }}">
        @if ($unit)
            <span class="unit">{{ $unit }}</span>
        @endif
        {{ $value }}
    </p>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
    @endif

    @if (isset($slot) && trim((string) $slot) !== '')
        <div class="mt-3 border-t border-slate-200 pt-2 text-xs text-slate-600">
            {{ $slot }}
        </div>
    @endif
</div>
