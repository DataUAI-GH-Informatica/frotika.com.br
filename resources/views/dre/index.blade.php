@extends('layouts.app')

@section('title', 'DRE veicular | Frotika')

@php
    use App\Domain\Fleet\Enums\VehicleType;

    $apportionment = $dre['apportionment'];
    $methodLabels = [
        'by_km' => 'Rateio por km rodado',
        'by_revenue' => 'Rateio por receita',
        'equal' => 'Rateio igual entre veículos',
        'none' => 'Sem rateio de despesas',
    ];
    $methodLabel = $methodLabels[$apportionment['method']] ?? 'Rateio';
@endphp

@section('content')
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="font-display text-xl font-semibold text-slate-900">DRE veicular</h1>
            <p class="mt-0.5 text-sm text-slate-500">
                {{ Format::date($filters['from']) }} — {{ Format::date($filters['to']) }}
                · {{ $methodLabel }}
            </p>
        </div>

        @if ($mode === 'individual')
            <x-ui.link-button href="{{ route('dre.index', ['from' => $filters['from'], 'to' => $filters['to']]) }}"
                variant="secondary" size="sm">← Comparativo da frota</x-ui.link-button>
        @endif
    </div>

    <form method="GET" action="{{ route('dre.index') }}"
        class="mb-4 grid gap-2 rounded-lg border border-slate-200 bg-white p-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
        <div>
            <label for="from" class="text-2xs font-semibold uppercase tracking-wide text-slate-500">Data do serviço — de</label>
            <input id="from" type="date" name="from" value="{{ $filters['from'] }}"
                class="mt-1 h-9 w-full rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-900" />
        </div>
        <div>
            <label for="to" class="text-2xs font-semibold uppercase tracking-wide text-slate-500">até</label>
            <input id="to" type="date" name="to" value="{{ $filters['to'] }}"
                class="mt-1 h-9 w-full rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-900" />
        </div>
        <div>
            <label for="vehicle" class="text-2xs font-semibold uppercase tracking-wide text-slate-500">Veículo</label>
            <select id="vehicle" name="vehicle" onchange="this.form.requestSubmit()"
                class="mt-1 h-9 w-full rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-900">
                <option value="">Todos — comparativo</option>
                @foreach ($vehicles as $vehicleOption)
                    <option value="{{ $vehicleOption->getKey() }}" @selected($filters['vehicle'] === (int) $vehicleOption->getKey())>
                        {{ Format::plate($vehicleOption->getAttribute('plate')) }} · {{ $vehicleOption->type->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <x-ui.button type="submit" class="w-full">Aplicar</x-ui.button>
            <x-ui.link-button href="{{ route('dre.index') }}" variant="secondary" class="w-full justify-center">Mês atual</x-ui.link-button>
        </div>
    </form>

    @if ($mode === 'comparative')
        @include('dre._comparative')
    @else
        @include('dre._individual')
    @endif
@endsection
