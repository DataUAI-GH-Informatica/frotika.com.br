@extends('layouts.app')

@section('title', 'Abastecimentos | Frotika')

@section('content')
    <div class="mb-4 flex items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-xl font-semibold text-slate-900">Abastecimentos</h1>
            <p class="mt-0.5 text-sm text-slate-500">{{ $fuelings->total() }} {{ \Illuminate\Support\Str::plural('abastecimento', $fuelings->total()) }}</p>
        </div>
        @if ($canManage)
            <div class="hidden lg:block">
                <x-ui.link-button href="{{ route('fuelings.create') }}" variant="primary">Novo abastecimento</x-ui.link-button>
            </div>
        @endif
    </div>

    <form method="GET" action="{{ route('fuelings.index') }}"
        class="mb-3 grid gap-2 rounded-lg border border-slate-200 bg-white p-3 sm:grid-cols-2 lg:grid-cols-6">
        <select name="vehicle" class="h-9 rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-900 lg:col-span-2">
            <option value="">Veículo: todos</option>
            @foreach ($vehicles as $vehicle)
                <option value="{{ $vehicle->getKey() }}" @selected($filters['vehicle'] === (int) $vehicle->getKey())>{{ Format::plate($vehicle->getAttribute('plate')) }}</option>
            @endforeach
        </select>

        <select name="product" class="h-9 rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-900">
            <option value="">Produto: todos</option>
            @foreach ($products as $product)
                <option value="{{ $product->value }}" @selected($filters['product'] === $product->value)>{{ $product->label() }}</option>
            @endforeach
        </select>

        <div class="flex gap-2 lg:col-span-2">
            <input type="date" name="from" value="{{ $filters['from'] }}" class="h-9 w-full rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-900" />
            <input type="date" name="to" value="{{ $filters['to'] }}" class="h-9 w-full rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-900" />
        </div>

        <div class="flex gap-2">
            <x-ui.button type="submit" class="w-full">Filtrar</x-ui.button>
            <x-ui.link-button href="{{ route('fuelings.index') }}" variant="secondary" class="w-full justify-center">Limpar</x-ui.link-button>
        </div>
    </form>

    <div class="rounded-lg border border-slate-200 bg-white">
        <div class="hidden overflow-auto md:block">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50">
                    <tr class="border-b border-slate-200">
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-wide text-slate-500">Data</th>
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-wide text-slate-500">Veículo</th>
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-wide text-slate-500">Produto</th>
                        <th class="px-3 py-2 text-right text-2xs font-semibold uppercase tracking-wide text-slate-500">Odômetro</th>
                        <th class="px-3 py-2 text-right text-2xs font-semibold uppercase tracking-wide text-slate-500">Litros</th>
                        <th class="px-3 py-2 text-right text-2xs font-semibold uppercase tracking-wide text-slate-500">km/l</th>
                        <th class="px-3 py-2 text-right text-2xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fuelings as $fueling)
                        <tr class="h-9 cursor-pointer border-b border-slate-100 hover:bg-slate-50"
                            onclick="window.location='{{ route('fuelings.show', ['fueling' => $fueling->getKey()]) }}'">
                            <td class="px-3 font-mono tabular text-slate-600">{{ Format::date($fueling->getAttribute('fueled_at')) }}</td>
                            <td class="px-3 font-mono tabular text-slate-900">{{ Format::plate($fueling->vehicle?->getAttribute('plate') ?? '—') }}</td>
                            <td class="px-3 text-slate-600">
                                {{ $fueling->product->label() }}
                                @if ($fueling->getAttribute('full_tank'))
                                    <span class="ml-1 inline-flex items-center rounded-full border border-brand-200 bg-brand-50 px-1.5 py-0.5 text-2xs font-semibold text-brand-700">cheio</span>
                                @endif
                            </td>
                            <td class="px-3 text-right font-mono tabular text-slate-600">{{ Format::km((int) $fueling->getAttribute('odometer')) }}</td>
                            <td class="px-3 text-right font-mono tabular text-slate-600">{{ Format::moneyDecimal((float) $fueling->getAttribute('liters'), 3) }}</td>
                            <td class="px-3 text-right font-mono tabular text-slate-600">{{ Format::consumption($fueling->getAttribute('km_per_liter') !== null ? (float) $fueling->getAttribute('km_per_liter') : null) }}</td>
                            <td class="px-3 text-right font-mono tabular text-slate-900">{{ Format::money((int) $fueling->getAttribute('total_cents')) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="px-4 py-12 text-center">
                                    <p class="font-display text-lg font-semibold text-slate-900">Nenhum abastecimento encontrado.</p>
                                    <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">Lance um abastecimento para acompanhar consumo e custo por veículo.</p>
                                    @if ($canManage)
                                        <div class="mt-4 flex justify-center">
                                            <x-ui.link-button href="{{ route('fuelings.create') }}" variant="primary">Novo abastecimento</x-ui.link-button>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($fuelings->isNotEmpty())
                    <tfoot class="sticky bottom-0 bg-slate-50">
                        <tr class="border-t border-slate-200 text-sm">
                            <td colspan="4" class="px-3 py-2 text-right text-2xs font-semibold uppercase tracking-wide text-slate-500">Total da página</td>
                            <td class="px-3 py-2 text-right font-mono font-semibold tabular text-slate-900">{{ Format::moneyDecimal($totals['liters'], 3) }}</td>
                            <td></td>
                            <td class="px-3 py-2 text-right font-mono font-semibold tabular text-slate-900">{{ Format::money($totals['total_cents']) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="divide-y divide-slate-100 md:hidden">
            @forelse ($fuelings as $fueling)
                <a href="{{ route('fuelings.show', ['fueling' => $fueling->getKey()]) }}" class="block px-4 py-3 active:bg-slate-50">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-mono tabular text-slate-900">{{ Format::plate($fueling->vehicle?->getAttribute('plate') ?? '—') }}</span>
                        <span class="shrink-0 font-mono tabular text-sm text-slate-900">{{ Format::money((int) $fueling->getAttribute('total_cents')) }}</span>
                    </div>
                    <div class="mt-0.5 flex items-center gap-2 text-xs text-slate-500">
                        <span class="font-mono tabular">{{ Format::date($fueling->getAttribute('fueled_at')) }}</span>
                        <span>·</span>
                        <span>{{ $fueling->product->label() }}</span>
                        <span>·</span>
                        <span class="font-mono tabular">{{ Format::consumption($fueling->getAttribute('km_per_liter') !== null ? (float) $fueling->getAttribute('km_per_liter') : null) }}</span>
                    </div>
                </a>
            @empty
                <div class="px-4 py-12 text-center">
                    <p class="font-display text-lg font-semibold text-slate-900">Nenhum abastecimento encontrado.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-4">{{ $fuelings->links() }}</div>

    @if ($canManage)
        <a href="{{ route('fuelings.create') }}"
            class="fixed bottom-20 right-4 z-20 flex size-14 items-center justify-center rounded-full bg-brand-700 text-white shadow-overlay active:bg-brand-800 md:hidden"
            aria-label="Novo abastecimento">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke-linecap="round" />
            </svg>
        </a>
    @endif
@endsection
