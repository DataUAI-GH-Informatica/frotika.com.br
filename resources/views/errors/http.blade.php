@php
    $status = (string) ($status ?? 'Erro');
    $title = $title ?? 'Erro ' . $status;
    $headline = $headline ?? 'Não foi possível concluir esta ação agora.';
    $message =
        $message ??
        'A tela não pôde ser carregada neste momento. Tente novamente em instantes ou retorne para o painel.';
    $tip = $tip ?? null;
    $tag = $tag ?? 'Operação interrompida';
    $requestId = request()->headers->get('X-Request-Id') ?? request()->headers->get('X-Correlation-Id');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title>{{ $title }} | Frotika</title>
    <link rel="icon" type="image/png" href="{{ asset('icone-frotika.png') }}" />
    <link rel="apple-touch-icon" href="{{ asset('icone-frotika.png') }}" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-brand-800/70 bg-brand-950 safe-t">
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('welcome') }}" class="inline-flex rounded-md">
                    <x-ui.brand on="dark" size="lg" />
                </a>

                <p class="hidden text-sm font-medium text-brand-100 md:block">Instrumento indisponível no momento</p>
            </div>
        </header>

        <main class="mx-auto flex w-full max-w-6xl flex-1 items-center px-4 py-6 sm:px-6 lg:px-8">
            <div class="grid w-full gap-4 lg:grid-cols-[minmax(0,1.45fr)_minmax(0,1fr)]">
                <x-ui.card class="p-5 sm:p-6">
                    <p class="text-2xs font-semibold uppercase tracking-[0.16em] text-slate-500">Erro HTTP</p>

                    <div class="mt-2 flex flex-wrap items-end gap-3">
                        <p class="font-mono text-2xl font-semibold text-slate-900 tabular">{{ $status }}</p>
                        <span
                            class="inline-flex items-center rounded-md border border-accent-300 bg-accent-50 px-2 py-0.5 text-2xs font-semibold uppercase tracking-[0.14em] text-accent-700">
                            {{ $tag }}
                        </span>
                    </div>

                    <h1 class="mt-4 font-display text-xl font-semibold text-slate-900">{{ $headline }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ $message }}</p>

                    @if ($tip)
                        <p class="mt-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                            {{ $tip }}
                        </p>
                    @endif

                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        <x-ui.link-button href="{{ route('welcome') }}" size="sm">
                            Ir para o início
                        </x-ui.link-button>

                        @auth
                            <x-ui.link-button href="{{ route('dashboard') }}" size="sm" variant="secondary">
                                Voltar ao painel
                            </x-ui.link-button>
                        @else
                            <x-ui.link-button href="{{ route('login') }}" size="sm" variant="secondary">
                                Entrar
                            </x-ui.link-button>
                        @endauth

                        <x-ui.button type="button" size="sm" variant="ghost" onclick="window.history.back()">
                            Voltar
                        </x-ui.button>
                    </div>
                </x-ui.card>

                <x-ui.card class="p-0">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Diagnóstico rápido
                        </p>
                    </div>

                    <div class="divide-y divide-slate-200">
                        <div class="grid min-h-(--spacing-row) grid-cols-[9rem_1fr] items-center gap-3 px-4 py-1.5">
                            <p class="text-xs font-medium text-slate-500">Data e hora</p>
                            <p class="font-mono text-right text-sm text-slate-900 tabular">
                                {{ now()->format('d/m/Y H:i') }}</p>
                        </div>

                        <div class="grid min-h-(--spacing-row) grid-cols-[9rem_1fr] items-center gap-3 px-4 py-1.5">
                            <p class="text-xs font-medium text-slate-500">Método</p>
                            <p class="font-mono text-right text-sm text-slate-900 tabular">{{ request()->method() }}
                            </p>
                        </div>

                        <div class="grid min-h-(--spacing-row) grid-cols-[9rem_1fr] items-center gap-3 px-4 py-1.5">
                            <p class="text-xs font-medium text-slate-500">Rota</p>
                            <p class="truncate font-mono text-right text-sm text-slate-900 tabular"
                                title="{{ request()->path() }}">
                                /{{ ltrim(request()->path(), '/') }}
                            </p>
                        </div>

                        @if ($requestId)
                            <div class="grid min-h-(--spacing-row) grid-cols-[9rem_1fr] items-center gap-3 px-4 py-1.5">
                                <p class="text-xs font-medium text-slate-500">Request ID</p>
                                <p class="truncate font-mono text-right text-sm text-slate-900 tabular"
                                    title="{{ $requestId }}">{{ $requestId }}</p>
                            </div>
                        @endif
                    </div>
                </x-ui.card>
            </div>
        </main>
    </div>
</body>

</html>
