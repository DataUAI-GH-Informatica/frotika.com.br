@props([
    'label',
    'value',
    'tone' => 'default',
    'hint' => null,
])

@php
    $valueToneClasses = [
        'default' => 'text-slate-900',
        'success' => 'text-success-700',
        'danger' => 'text-danger-700',
        'info' => 'text-info-700',
        'warning' => 'text-warning-700',
    ][$tone] ?? 'text-slate-900';
@endphp

<x-ui.card class="h-full">
    <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">{{ $label }}</p>
    <p class="mt-2 text-right font-mono text-2xl font-semibold tabular {{ $valueToneClasses }}">{{ $value }}</p>

    @if ($hint)
        <p class="mt-2 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if (isset($slot) && trim((string) $slot) !== '')
        <div class="mt-3 border-t border-slate-200 pt-2 text-xs text-slate-600">
            {{ $slot }}
        </div>
    @endif
</x-ui.card>
