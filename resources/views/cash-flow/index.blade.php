@extends('layouts.app')

@section('title', 'Fluxo de caixa | Frotika')

@php
    $totals = $matrix['totals'];
    $netTone = $totals['net_cents'] < 0 ? 'danger' : 'default';
    $closingTone = $totals['closing_balance_cents'] < 0 ? 'danger' : 'default';
@endphp

@section('content')
    <div class="mb-4 flex items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-xl font-semibold text-slate-900">Fluxo de caixa</h1>
            <p class="mt-0.5 text-sm text-slate-500">
                {{ Format::date($matrix['from']) }} — {{ Format::date($matrix['to']) }}
                · {{ $matrix['include_forecast'] ? 'realizado + previsto' : 'somente realizado' }}
            </p>
        </div>
    </div>

    <form method="GET" action="{{ route('cash-flow.index') }}"
        class="mb-4 grid gap-2 rounded-lg border border-slate-200 bg-white p-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
        <div>
            <label for="from" class="text-2xs font-semibold uppercase tracking-wide text-slate-500">De</label>
            <input id="from" type="date" name="from" value="{{ $filters['from'] }}"
                class="mt-1 h-9 w-full rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-900" />
        </div>
        <div>
            <label for="to" class="text-2xs font-semibold uppercase tracking-wide text-slate-500">Até</label>
            <input id="to" type="date" name="to" value="{{ $filters['to'] }}"
                class="mt-1 h-9 w-full rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-900" />
        </div>
        <div>
            <label for="account" class="text-2xs font-semibold uppercase tracking-wide text-slate-500">Conta</label>
            <select id="account" name="account"
                class="mt-1 h-9 w-full rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-900">
                <option value="">Todas</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->getKey() }}" @selected($filters['account'] === (int) $account->getKey())>{{ $account->getAttribute('name') }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex h-9 items-center gap-2 text-sm text-slate-700">
            <input type="hidden" name="forecast" value="0" />
            <input type="checkbox" name="forecast" value="1" @checked($filters['forecast'])
                class="size-4 rounded border-slate-300 text-brand-700 focus:ring-2 focus:ring-brand-500/30" />
            Incluir previstos
        </label>
        <div class="flex gap-2">
            <x-ui.button type="submit" class="w-full">Aplicar</x-ui.button>
            <x-ui.link-button href="{{ route('cash-flow.index') }}" variant="secondary" class="w-full justify-center">Mês atual</x-ui.link-button>
        </div>
    </form>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <x-ui.stat-card label="Saldo inicial" :value="Format::money($totals['opening_balance_cents'])" />
        <x-ui.stat-card label="Entradas" :value="Format::money($totals['revenue_cents'])" tone="success" />
        <x-ui.stat-card label="Saídas" :value="Format::money(-$totals['expense_cents'])" tone="danger" />
        <x-ui.stat-card label="Resultado" :value="Format::money($totals['net_cents'])" :tone="$netTone" />
        <x-ui.stat-card label="Saldo final" :value="Format::money($totals['closing_balance_cents'])" :tone="$closingTone" />
    </div>

    @if (count($matrix['accounts']) > 1)
        <div class="mt-6 rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-200 px-3 py-2">
                <h2 class="text-2xs font-semibold uppercase tracking-wide text-slate-500">Por conta</h2>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-2xs uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-1.5 text-left">Conta</th>
                        <th class="px-3 py-1.5 text-right">Saldo inicial</th>
                        <th class="px-3 py-1.5 text-right">Entradas</th>
                        <th class="px-3 py-1.5 text-right">Saídas</th>
                        <th class="px-3 py-1.5 text-right">Saldo final</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($matrix['accounts'] as $account)
                        <tr class="h-9 border-b border-slate-100">
                            <td class="px-3 text-slate-900">{{ $account['name'] }}</td>
                            <td class="px-3 text-right font-mono tabular text-slate-600">{{ Format::money($account['opening_balance_cents']) }}</td>
                            <td class="px-3 text-right font-mono tabular text-success-700">{{ Format::money($account['revenue_cents']) }}</td>
                            <td class="px-3 text-right font-mono tabular text-danger-700">{{ Format::money(-$account['expense_cents']) }}</td>
                            <td @class([
                                'px-3 text-right font-mono tabular',
                                'text-danger-700' => $account['closing_balance_cents'] < 0,
                                'text-slate-900' => $account['closing_balance_cents'] >= 0,
                            ])>{{ Format::money($account['closing_balance_cents']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="mt-6 rounded-lg border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-3 py-2">
            <h2 class="text-2xs font-semibold uppercase tracking-wide text-slate-500">Movimento diário</h2>
        </div>
        <div class="overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50">
                    <tr class="border-b border-slate-200 text-2xs uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-2 text-left">Dia</th>
                        <th class="px-3 py-2 text-right">Entradas</th>
                        <th class="px-3 py-2 text-right">Saídas</th>
                        <th class="px-3 py-2 text-right">Resultado</th>
                        <th class="px-3 py-2 text-right">Saldo acumulado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($days as $day)
                        @php $movement = $day['revenue_cents'] !== 0 || $day['expense_cents'] !== 0; @endphp
                        <tr @class([
                            'h-9 border-b border-slate-100',
                            'text-slate-400' => ! $movement,
                            'hover:bg-slate-50' => $movement,
                        ])>
                            <td class="px-3 font-mono tabular">{{ Format::date($day['date']) }}</td>
                            <td class="px-3 text-right font-mono tabular {{ $movement && $day['revenue_cents'] > 0 ? 'text-success-700' : '' }}">{{ $day['revenue_cents'] > 0 ? Format::money($day['revenue_cents']) : '—' }}</td>
                            <td class="px-3 text-right font-mono tabular {{ $movement && $day['expense_cents'] > 0 ? 'text-danger-700' : '' }}">{{ $day['expense_cents'] > 0 ? Format::money(-$day['expense_cents']) : '—' }}</td>
                            <td @class([
                                'px-3 text-right font-mono tabular',
                                'text-danger-700' => $movement && $day['net_cents'] < 0,
                                'text-slate-900' => $movement && $day['net_cents'] >= 0,
                            ])>{{ $movement ? Format::money($day['net_cents']) : '—' }}</td>
                            <td @class([
                                'px-3 text-right font-mono tabular font-medium',
                                'text-danger-700' => $day['running_balance_cents'] < 0,
                                'text-slate-900' => $day['running_balance_cents'] >= 0,
                            ])>{{ Format::money($day['running_balance_cents']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="px-4 py-12 text-center">
                                    <p class="font-display text-lg font-semibold text-slate-900">Nenhuma conta ativa no período.</p>
                                    <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">Cadastre uma conta bancária para acompanhar o fluxo de caixa.</p>
                                    <div class="mt-4 flex justify-center">
                                        <x-ui.link-button href="{{ route('bank-accounts.index') }}" variant="secondary">Contas bancárias</x-ui.link-button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
