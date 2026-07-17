@props(['name', 'label' => null, 'required' => false])

@php
    $selectId = $attributes->get('id', $name);
    $hasError = $errors->has($name);

    $selectBaseClasses =
        'mt-1.5 block h-11 w-full rounded-md border bg-white px-3 text-base text-slate-900 transition-colors sm:h-9 sm:text-sm';
    $selectStateClasses = $hasError
        ? 'border-danger-700 focus:border-danger-700 focus:ring-2 focus:ring-danger-500/20'
        : 'border-slate-300 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20';
@endphp

<div>
    @if ($label)
        <label for="{{ $selectId }}" class="text-sm font-medium text-slate-700">
            {{ $label }}
            @if ($required)
                <span class="text-danger-700" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <select id="{{ $selectId }}" name="{{ $name }}" @required($required)
        {{ $attributes->merge(['class' => $selectBaseClasses . ' ' . $selectStateClasses]) }}>
        {{ $slot }}
    </select>

    @error($name)
        <p class="mt-1 text-sm text-danger-700">{{ $message }}</p>
    @enderror
</div>
