@extends('platform.layout')

@section('title', $group->name.' | Administração Frotika')

@section('content')
    <x-ui.page-header title="{{ $group->name }}" subtitle="Licenças, faturas e usuários do grupo">
        <x-slot:actions>
            <a href="{{ route('platform.groups.index') }}"
                class="inline-flex h-9 items-center rounded-md border border-slate-300 px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Voltar
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <section class="mb-4 rounded-lg border border-slate-200 bg-white p-4">
        <p class="text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">Responsável</p>
        <p class="mt-1 text-sm text-slate-900">
            {{ $group->owner?->name ?? '—' }}
            @if ($group->owner?->email)
                <span class="text-slate-500">· {{ $group->owner->email }}</span>
            @endif
        </p>
    </section>

    <section class="mb-4 rounded-lg border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-4 py-2.5">
            <h2 class="font-display text-lg font-semibold text-slate-900">Licenças por empresa</h2>
            <p class="text-xs text-slate-500">Lance o boleto mensal e dê baixa manual no pagamento.</p>
        </div>

        <div class="overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50">
                    <tr class="border-b border-slate-200">
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            Empresa</th>
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            Status</th>
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            Último boleto</th>
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            Lançar / baixar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($licenses as $license)
                        @php($invoice = $license->latestInvoice)
                        <tr class="align-top border-b border-slate-100">
                            <td class="px-3 py-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-slate-900">
                                        {{ $license->company?->getAttribute('trade_name') ?? 'Empresa sem nome' }}
                                    </span>
                                    @if ($license->is_primary)
                                        <span
                                            class="rounded-md border border-accent-200 bg-accent-50 px-2 py-0.5 text-2xs font-semibold text-accent-700">
                                            Principal
                                        </span>
                                    @endif
                                </div>
                                @if ($license->company?->getAttribute('cnpj'))
                                    <span class="font-mono text-xs text-slate-500 tabular">
                                        {{ Format::cnpj($license->company->getAttribute('cnpj')) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5">
                                <span class="font-medium text-slate-700">{{ $license->status->label() }}</span>
                                @if ($license->trial_ends_at)
                                    <span class="block text-xs text-slate-500">Trial até
                                        {{ Format::date($license->trial_ends_at) }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5">
                                @if ($invoice)
                                    <p class="font-mono text-sm text-slate-900 tabular">
                                        <span class="unit">R$</span>
                                        {{ Format::moneyDecimal($invoice->amount_cents / 100) }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ $invoice->status->label() }} · venc. {{ Format::date($invoice->due_date) }}
                                    </p>
                                    <div class="mt-1 flex flex-wrap gap-1.5 text-xs">
                                        @if ($invoice->boleto_url)
                                            <a href="{{ $invoice->boleto_url }}" target="_blank" rel="noopener"
                                                class="rounded border border-brand-300 px-2 py-0.5 font-medium text-brand-700 hover:bg-brand-50">
                                                Abrir boleto
                                            </a>
                                        @endif
                                        @if ($invoice->boleto_pdf_url)
                                            <a href="{{ $invoice->boleto_pdf_url }}" target="_blank" rel="noopener"
                                                class="rounded border border-slate-300 px-2 py-0.5 font-medium text-slate-700 hover:bg-slate-50">
                                                PDF
                                            </a>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-500">Sem boleto lançado</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5">
                                <form method="POST"
                                    action="{{ route('platform.licenses.issue', ['license' => $license->getKey()]) }}"
                                    class="grid gap-1.5 rounded-md border border-slate-200 bg-slate-50 p-2.5">
                                    @csrf
                                    <label class="text-2xs font-medium text-slate-600"
                                        for="amount_cents_{{ $license->getKey() }}">Mensalidade (centavos)</label>
                                    <input id="amount_cents_{{ $license->getKey() }}" name="amount_cents" type="number"
                                        min="1" value="{{ $license->monthly_price_cents ?: $defaultMonthlyPriceCents }}"
                                        class="h-8 rounded border border-slate-300 px-2 font-mono text-sm text-slate-900 tabular focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                                        required />

                                    <label class="text-2xs font-medium text-slate-600"
                                        for="due_date_{{ $license->getKey() }}">Vencimento</label>
                                    <input id="due_date_{{ $license->getKey() }}" name="due_date" type="date"
                                        value="{{ now()->addDays(3)->toDateString() }}"
                                        class="h-8 rounded border border-slate-300 px-2 text-sm text-slate-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20"
                                        required />

                                    <label class="text-2xs font-medium text-slate-600"
                                        for="reference_month_{{ $license->getKey() }}">Competência (AAAA-MM)</label>
                                    <input id="reference_month_{{ $license->getKey() }}" name="reference_month"
                                        type="month" value="{{ now()->format('Y-m') }}"
                                        class="h-8 rounded border border-slate-300 px-2 text-sm text-slate-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20" />

                                    <label class="text-2xs font-medium text-slate-600"
                                        for="boleto_number_{{ $license->getKey() }}">Linha digitável</label>
                                    <input id="boleto_number_{{ $license->getKey() }}" name="boleto_number" type="text"
                                        placeholder="Opcional"
                                        class="h-8 rounded border border-slate-300 px-2 text-sm text-slate-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20" />

                                    <label class="text-2xs font-medium text-slate-600"
                                        for="boleto_url_{{ $license->getKey() }}">URL do boleto</label>
                                    <input id="boleto_url_{{ $license->getKey() }}" name="boleto_url" type="url"
                                        placeholder="https://..."
                                        class="h-8 rounded border border-slate-300 px-2 text-sm text-slate-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20" />

                                    <label class="text-2xs font-medium text-slate-600"
                                        for="boleto_pdf_url_{{ $license->getKey() }}">URL do PDF</label>
                                    <input id="boleto_pdf_url_{{ $license->getKey() }}" name="boleto_pdf_url" type="url"
                                        placeholder="https://..."
                                        class="h-8 rounded border border-slate-300 px-2 text-sm text-slate-900 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20" />

                                    <x-ui.button type="submit" size="sm" class="mt-1">Lançar boleto</x-ui.button>
                                </form>

                                @if ($invoice && in_array($invoice->status->value, ['pending', 'overdue'], true))
                                    <form method="POST"
                                        action="{{ route('platform.invoices.mark-paid', ['invoice' => $invoice->getKey()]) }}"
                                        class="mt-2 grid gap-1.5 rounded-md border border-success-200 bg-success-50 p-2.5">
                                        @csrf
                                        <label class="text-2xs font-medium text-success-700"
                                            for="paid_at_{{ $invoice->getKey() }}">Data do pagamento</label>
                                        <input id="paid_at_{{ $invoice->getKey() }}" name="paid_at" type="date"
                                            value="{{ now()->toDateString() }}"
                                            class="h-8 rounded border border-success-200 px-2 text-sm text-slate-900 focus:border-success-500 focus:ring-2 focus:ring-success-500/20" />
                                        <label class="text-2xs font-medium text-success-700"
                                            for="paid_note_{{ $invoice->getKey() }}">Observação</label>
                                        <input id="paid_note_{{ $invoice->getKey() }}" name="paid_note" type="text"
                                            placeholder="Baixa manual conferida"
                                            class="h-8 rounded border border-success-200 px-2 text-sm text-slate-900 focus:border-success-500 focus:ring-2 focus:ring-success-500/20" />
                                        <x-ui.button type="submit" size="sm" variant="secondary">Confirmar
                                            pagamento</x-ui.button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500">
                                Nenhuma licença encontrada para este grupo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-4 py-2.5">
            <h2 class="font-display text-lg font-semibold text-slate-900">Usuários do grupo</h2>
        </div>
        <div class="overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50">
                    <tr class="border-b border-slate-200">
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            Nome</th>
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            E-mail</th>
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-[0.12em] text-slate-500">
                            Papel</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $member)
                        <tr class="h-9 border-b border-slate-100">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ $member->name }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $member->email }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $member->pivot->role ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-6 text-center text-sm text-slate-500">
                                Nenhum usuário no grupo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
