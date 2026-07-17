@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
])

@php
    $baseClasses =
        'inline-flex items-center justify-center gap-2 rounded-md font-medium no-select transition-colors focus:outline-none disabled:pointer-events-none disabled:opacity-60';

    $sizeClasses =
        [
            'sm' => 'h-8 px-3 text-sm',
            'md' => 'h-9 px-4 text-sm',
        ][$size] ?? 'h-9 px-4 text-sm';

    $variantClasses =
        [
            'primary' => 'bg-brand-700 text-white hover:bg-brand-600 active:bg-brand-800 focus-visible:ring-2 focus-visible:ring-brand-500/35',
            'secondary' =>
                'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 active:bg-slate-100 focus-visible:ring-2 focus-visible:ring-brand-500/30',
            'ghost' => 'bg-transparent text-slate-700 hover:bg-slate-100 active:bg-slate-200 focus-visible:ring-2 focus-visible:ring-brand-500/30',
            'danger' => 'bg-danger-700 text-white hover:bg-danger-500 active:bg-danger-700 focus-visible:ring-2 focus-visible:ring-danger-500/35',
        ][$variant] ??
        'bg-brand-700 text-white hover:bg-brand-600 active:bg-brand-800 focus-visible:ring-2 focus-visible:ring-brand-500/35';
@endphp

<button type="{{ $type }}"
    {{ $attributes->merge(['class' => $baseClasses . ' ' . $sizeClasses . ' ' . $variantClasses]) }}>
    {{ $slot }}
</button>
