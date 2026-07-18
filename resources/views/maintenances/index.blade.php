@extends('layouts.app')

@section('title', 'Manutenções | Frotika')

@php
    use App\Domain\Maintenances\Enums\MaintenanceStatus;

    $statusChip = fn ($status) => match ($status) {
        MaintenanceStatus::Completed => 'border-success-300 bg-success-50 text-success-700',
        MaintenanceStatus::InProgress => 'border-warning-300 bg-warning-50 text-warning-700',
        MaintenanceStatus::Open => 'border-brand-200 bg-brand-50 text-brand-700',
        default => 'border-slate-300 bg-slate-50 text-slate-400 line-through',
    };
@endphp

@section('content')
    <div class="mb-4 flex items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-xl font-semibold text-slate-900">Manutenções</h1>
            <p class="mt-0.5 text-sm text-slate-500">{{ $maintenances->total() }} {{ \Illuminate\Support\Str::plural('manutenção', $maintenances->total()) }}</p>
        </div>
        @if ($canManage)
            <div class="hidden lg:block">
                <x-ui.link-button href="{{ route('maintenances.create') }}" variant="primary">Nova manutenção</x-ui.link-button>
            </div>
        @endif
    </div>

    <form method="GET" action="{{ route('maintenances.index') }}"
        class="mb-3 grid gap-2 rounded-lg border border-slate-200 bg-white p-3 sm:grid-cols-2 lg:grid-cols-6">
        <select name="vehicle" class="h-9 rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-900 lg:col-span-2">
            <option value="">Veículo: todos</option>
            @foreach ($vehicles as $vehicle)
                <option value="{{ $vehicle->getKey() }}" @selected($filters['vehicle'] === (int) $vehicle->getKey())>{{ Format::plate($vehicle->getAttribute('plate')) }}</option>
            @endforeach
        </select>

        <select name="type" class="h-9 rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-900">
            <option value="">Tipo: todos</option>
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected($filters['type'] === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </select>

        <select name="status" class="h-9 rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-900">
            <option value="">Situação: todas</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>

        <div class="flex gap-2 lg:col-span-2">
            <input type="date" name="from" value="{{ $filters['from'] }}" class="h-9 w-full rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-900" />
            <input type="date" name="to" value="{{ $filters['to'] }}" class="h-9 w-full rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-900" />
        </div>

        <div class="flex gap-2 lg:col-span-6">
            <x-ui.button type="submit">Filtrar</x-ui.button>
            <x-ui.link-button href="{{ route('maintenances.index') }}" variant="secondary" class="justify-center">Limpar</x-ui.link-button>
        </div>
    </form>

    <div class="rounded-lg border border-slate-200 bg-white">
        <div class="hidden overflow-auto md:block">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50">
                    <tr class="border-b border-slate-200">
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-wide text-slate-500">Abertura</th>
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-wide text-slate-500">Veículo</th>
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-wide text-slate-500">Tipo / Categoria</th>
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-wide text-slate-500">Situação</th>
                        <th class="px-3 py-2 text-right text-2xs font-semibold uppercase tracking-wide text-slate-500">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($maintenances as $maintenance)
                        <tr class="h-9 cursor-pointer border-b border-slate-100 hover:bg-slate-50"
                            onclick="window.location='{{ route('maintenances.show', ['maintenance' => $maintenance->getKey()]) }}'">
                            <td class="px-3 font-mono tabular text-slate-600">{{ Format::date($maintenance->getAttribute('opened_at')) }}</td>
                            <td class="px-3 font-mono tabular text-slate-900">{{ Format::plate($maintenance->vehicle?->getAttribute('plate') ?? '—') }}</td>
                            <td class="px-3 text-slate-600">{{ $maintenance->type->label() }} <span class="text-slate-400">·</span> {{ $maintenance->category->label() }}</td>
                            <td class="px-3"><span class="inline-flex items-center rounded-full border px-2 py-0.5 text-2xs font-semibold {{ $statusChip($maintenance->getAttribute('status')) }}">{{ $maintenance->getAttribute('status')->label() }}</span></td>
                            <td class="px-3 text-right font-mono tabular text-slate-900">{{ Format::money((int) $maintenance->getAttribute('total_cents')) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="px-4 py-12 text-center">
                                    <p class="font-display text-lg font-semibold text-slate-900">Nenhuma manutenção encontrada.</p>
                                    <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">Registre manutenções para acompanhar o custo por veículo no DRE.</p>
                                    @if ($canManage)
                                        <div class="mt-4 flex justify-center">
                                            <x-ui.link-button href="{{ route('maintenances.create') }}" variant="primary">Nova manutenção</x-ui.link-button>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($maintenances->isNotEmpty())
                    <tfoot class="sticky bottom-0 bg-slate-50">
                        <tr class="border-t border-slate-200 text-sm">
                            <td colspan="4" class="px-3 py-2 text-right text-2xs font-semibold uppercase tracking-wide text-slate-500">Total (exceto canceladas)</td>
                            <td class="px-3 py-2 text-right font-mono font-semibold tabular text-slate-900">{{ Format::money($totalCents) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="divide-y divide-slate-100 md:hidden">
            @forelse ($maintenances as $maintenance)
                <a href="{{ route('maintenances.show', ['maintenance' => $maintenance->getKey()]) }}" class="block px-4 py-3 active:bg-slate-50">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-mono tabular text-slate-900">{{ Format::plate($maintenance->vehicle?->getAttribute('plate') ?? '—') }}</span>
                        <span class="shrink-0 font-mono tabular text-sm text-slate-900">{{ Format::money((int) $maintenance->getAttribute('total_cents')) }}</span>
                    </div>
                    <div class="mt-0.5 flex items-center gap-2 text-xs text-slate-500">
                        <span class="font-mono tabular">{{ Format::date($maintenance->getAttribute('opened_at')) }}</span>
                        <span>·</span>
                        <span>{{ $maintenance->type->label() }}</span>
                        <span class="inline-flex items-center rounded-full border px-1.5 py-0.5 text-2xs font-semibold {{ $statusChip($maintenance->getAttribute('status')) }}">{{ $maintenance->getAttribute('status')->label() }}</span>
                    </div>
                </a>
            @empty
                <div class="px-4 py-12 text-center">
                    <p class="font-display text-lg font-semibold text-slate-900">Nenhuma manutenção encontrada.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-4">{{ $maintenances->links() }}</div>

    @if ($canManage)
        <a href="{{ route('maintenances.create') }}"
            class="fixed bottom-20 right-4 z-20 flex size-14 items-center justify-center rounded-full bg-brand-700 text-white shadow-overlay active:bg-brand-800 md:hidden"
            aria-label="Nova manutenção">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke-linecap="round" />
            </svg>
        </a>
    @endif
@endsection
