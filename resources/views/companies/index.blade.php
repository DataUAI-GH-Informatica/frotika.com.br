@extends('layouts.app')

@section('title', 'Empresas | Frotika')

@section('content')
    <div class="mb-4 flex items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-xl font-semibold text-slate-900">Empresas</h1>
            <p class="mt-0.5 text-sm text-slate-500">
                {{ $companies->count() }} {{ \Illuminate\Support\Str::plural('empresa', $companies->count()) }} no grupo
            </p>
        </div>

        @if ($canManage)
            <div class="hidden lg:block">
                <x-ui.link-button href="{{ route('companies.create') }}" variant="primary">
                    Nova empresa
                </x-ui.link-button>
            </div>
        @endif
    </div>

    <div class="rounded-lg border border-slate-200 bg-white">
        {{-- Desktop --}}
        <div class="hidden overflow-auto md:block">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50">
                    <tr class="border-b border-slate-200">
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-wide text-slate-500">
                            Empresa</th>
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-wide text-slate-500">
                            CNPJ</th>
                        <th class="px-3 py-2 text-left text-2xs font-semibold uppercase tracking-wide text-slate-500">
                            Cidade/UF</th>
                        <th class="w-56 px-3 py-2 text-right text-2xs font-semibold uppercase tracking-wide text-slate-500">
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($companies as $company)
                        @php($isCurrent = (int) $currentCompanyId === $company->getKey())
                        <tr class="h-9 border-b border-slate-100 hover:bg-slate-50">
                            <td class="px-3">
                                <a href="{{ route('companies.show', ['company' => $company->getKey()]) }}"
                                    class="font-medium text-slate-900 hover:text-brand-700">
                                    {{ $company->getAttribute('trade_name') }}
                                </a>
                                @if ($isCurrent)
                                    <span
                                        class="ml-1 inline-flex items-center rounded-full border border-accent-500/50 bg-accent-500/10 px-2 py-0.5 text-2xs font-semibold text-accent-700">
                                        Ativa
                                    </span>
                                @endif
                                <span class="block text-xs text-slate-400">{{ $company->getAttribute('legal_name') }}</span>
                            </td>
                            <td class="px-3 font-mono text-xs text-slate-600 tabular">
                                {{ Format::cnpj($company->getAttribute('cnpj')) }}</td>
                            <td class="px-3 text-slate-600">
                                {{ collect([$company->getAttribute('city'), $company->getAttribute('state')])->filter()->join('/') ?: '—' }}
                            </td>
                            <td class="px-3 text-right">
                                <div class="flex items-center justify-end gap-2 text-xs">
                                    @if (! $isCurrent)
                                        <form method="POST" action="{{ route('tenancy.switch-company') }}">
                                            @csrf
                                            <input type="hidden" name="company_id" value="{{ $company->getKey() }}" />
                                            <button type="submit"
                                                class="font-medium text-slate-500 hover:text-brand-700">Definir ativa</button>
                                        </form>
                                    @endif
                                    @if ($canManage)
                                        <a href="{{ route('companies.edit', ['company' => $company->getKey()]) }}"
                                            class="font-medium text-brand-700 hover:underline">Editar</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="px-4 py-12 text-center">
                                    <p class="font-display text-lg font-semibold text-slate-900">Nenhuma empresa cadastrada.
                                    </p>
                                    <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                                        Cadastre as empresas (CNPJs) do seu grupo para lançar viagens, abastecimentos e
                                        despesas em cada uma.
                                    </p>
                                    @if ($canManage)
                                        <div class="mt-4 flex justify-center">
                                            <x-ui.link-button href="{{ route('companies.create') }}" variant="primary">
                                                Nova empresa
                                            </x-ui.link-button>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile --}}
        <div class="divide-y divide-slate-100 md:hidden">
            @forelse ($companies as $company)
                @php($isCurrent = (int) $currentCompanyId === $company->getKey())
                <a href="{{ route('companies.show', ['company' => $company->getKey()]) }}"
                    class="block px-4 py-3 active:bg-slate-50">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-medium text-slate-900">{{ $company->getAttribute('trade_name') }}</span>
                        @if ($isCurrent)
                            <span
                                class="inline-flex items-center rounded-full border border-accent-500/50 bg-accent-500/10 px-2 py-0.5 text-2xs font-semibold text-accent-700">Ativa</span>
                        @endif
                    </div>
                    <div class="mt-0.5 font-mono text-xs text-slate-500 tabular">
                        {{ Format::cnpj($company->getAttribute('cnpj')) }}</div>
                </a>
            @empty
                <div class="px-4 py-12 text-center">
                    <p class="font-display text-lg font-semibold text-slate-900">Nenhuma empresa cadastrada.</p>
                    <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">Cadastre as empresas do seu grupo para começar.
                    </p>
                </div>
            @endforelse
        </div>
    </div>

    @if ($canManage)
        <a href="{{ route('companies.create') }}"
            class="fixed bottom-20 right-4 z-20 flex size-14 items-center justify-center rounded-full bg-brand-700 text-white active:bg-brand-800 md:hidden shadow-overlay"
            aria-label="Nova empresa">
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke-linecap="round" />
            </svg>
        </a>
    @endif
@endsection
