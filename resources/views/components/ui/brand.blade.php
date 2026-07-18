@props([
    'on' => 'dark',
    'wordmark' => true,
    'size' => 'md',
])

@php
    // `on` = a cor do fundo onde a marca vive. Em fundo escuro o wordmark navy
    // da logo somiria, então o ícone vai num chip branco e o texto fica branco.
    $box = match ($size) {
        'sm' => 'h-7 w-7',
        'lg' => 'h-10 w-10',
        default => 'h-8 w-8',
    };
    $word = match ($size) {
        'sm' => 'text-sm',
        'lg' => 'text-lg',
        default => 'text-base',
    };
    $icon = asset('icone-frotika.png');
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    @if ($on === 'dark')
        <span class="inline-flex {{ $box }} items-center justify-center rounded-md bg-white p-1">
            <img src="{{ $icon }}" alt="Frotika" class="h-full w-full object-contain" />
        </span>
        @if ($wordmark)
            <span class="font-display {{ $word }} font-semibold text-white">Frotika</span>
        @endif
    @else
        <img src="{{ $icon }}" alt="Frotika" class="{{ $box }} object-contain" />
        @if ($wordmark)
            <span class="font-display {{ $word }} font-semibold text-brand-900">Frotika</span>
        @endif
    @endif
</span>
