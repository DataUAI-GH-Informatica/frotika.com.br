@props([
    'title',
    'subtitle' => null,
])

<div class="mb-4 border-b border-slate-200 pb-3">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="font-display text-xl font-semibold text-slate-900">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>

        @if (isset($actions))
            <div class="flex flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>

    @if (isset($slot) && trim((string) $slot) !== '')
        <div class="mt-3 text-sm text-slate-600">
            {{ $slot }}
        </div>
    @endif
</div>
