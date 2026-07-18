@extends('platform.layout')

@section('title', 'Grupos | Administração Frotika')

@section('content')
    <x-ui.page-header title="Grupos cadastrados"
        subtitle="Empresas clientes da plataforma, licença do grupo e faturamento recorrente" />

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
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            Licença</th>
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
                            <td class="px-3 py-2">
                                <span @class([
                                    'inline-flex items-center rounded-full border px-2 py-0.5 text-2xs font-semibold',
                                    'border-success-200 bg-success-50 text-success-700' => $row['status_value'] === 'active',
                                    'border-slate-200 bg-slate-50 text-slate-600' => $row['status_value'] === 'trialing',
                                    'border-warning-300 bg-warning-50 text-warning-700' => $row['status_value'] === 'pending_payment',
                                    'border-danger-300 bg-danger-50 text-danger-700' => $row['status_value'] === 'suspended',
                                    'border-slate-200 bg-slate-50 text-slate-400' => $row['status_value'] === null,
                                ])>{{ $row['status_label'] }}</span>
                            </td>
                            <td class="px-3 py-2 text-right font-mono text-slate-900 tabular">
                                {{ Format::moneyDecimal($row['mrr_cents'] / 100) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-sm text-slate-500">
                                Nenhum grupo cliente cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
