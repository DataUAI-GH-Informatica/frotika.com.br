@extends('platform.layout')

@section('title', 'Grupos | Administração Frotika')

@section('content')
    <x-ui.page-header title="Grupos cadastrados"
        subtitle="Empresas clientes da plataforma, licenças e faturamento recorrente" />

    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Grupos</p>
            <p class="mt-1 font-display text-2xl font-semibold text-slate-900 tabular">{{ $totalGroups }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">MRR (licenças ativas)</p>
            <p class="mt-1 font-display text-2xl font-semibold text-slate-900 tabular">
                <span class="text-base text-slate-500">R$</span> {{ Format::moneyDecimal($totalMrrCents / 100) }}
            </p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Licenças pendentes</p>
            <p @class([
                'mt-1 font-display text-2xl font-semibold tabular',
                'text-danger-600' => $totalPending > 0,
                'text-slate-900' => $totalPending === 0,
            ])>{{ $totalPending }}</p>
        </div>
    </div>

    <section class="rounded-lg border border-slate-200 bg-white">
        <div class="overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50">
                    <tr class="border-b border-slate-200">
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            Grupo</th>
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            Responsável</th>
                        <th class="px-3 py-2 text-right text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            Empresas</th>
                        <th class="px-3 py-2 text-right text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            Trial</th>
                        <th class="px-3 py-2 text-right text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            Ativas</th>
                        <th class="px-3 py-2 text-right text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            Pendentes</th>
                        <th class="px-3 py-2 text-right text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            MRR</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php($group = $row['group'])
                        <tr class="h-9 cursor-pointer border-b border-slate-100 hover:bg-brand-50"
                            onclick="window.location='{{ route('platform.groups.show', ['group' => $group->getKey()]) }}'">
                            <td class="px-3 py-2">
                                <a href="{{ route('platform.groups.show', ['group' => $group->getKey()]) }}"
                                    class="font-medium text-brand-700 hover:underline">
                                    {{ $group->name }}
                                </a>
                            </td>
                            <td class="px-3 py-2 text-slate-600">
                                {{ $group->owner?->name ?? '—' }}
                                @if ($group->owner?->email)
                                    <span class="block text-xs text-slate-400">{{ $group->owner->email }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right font-mono text-slate-900 tabular">{{ $row['companies_count'] }}</td>
                            <td class="px-3 py-2 text-right font-mono text-slate-600 tabular">{{ $row['trial_count'] }}</td>
                            <td class="px-3 py-2 text-right font-mono text-success-600 tabular">{{ $row['active_count'] }}</td>
                            <td @class([
                                'px-3 py-2 text-right font-mono tabular',
                                'text-danger-600' => $row['pending_count'] > 0,
                                'text-slate-400' => $row['pending_count'] === 0,
                            ])>{{ $row['pending_count'] }}</td>
                            <td class="px-3 py-2 text-right font-mono text-slate-900 tabular">
                                {{ Format::moneyDecimal($row['mrr_cents'] / 100) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-sm text-slate-500">
                                Nenhum grupo cliente cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
