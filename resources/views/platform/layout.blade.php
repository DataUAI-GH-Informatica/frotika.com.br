<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title>@yield('title', 'Administração | Frotika')</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
    <div class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-brand-950 text-brand-100">
            <div class="flex h-14 items-center gap-3 px-4 sm:px-6">
                <a href="{{ route('platform.groups.index') }}" class="inline-flex items-center gap-2">
                    <span
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-accent-500 font-display text-sm font-semibold text-brand-950">F</span>
                    <span class="font-display text-base font-semibold text-white">Frotika</span>
                    <span class="rounded-full border border-accent-500/50 bg-accent-500/10 px-2 py-0.5 text-2xs font-semibold uppercase tracking-widest text-accent-300">
                        Plataforma
                    </span>
                </a>

                <nav class="ml-4 hidden items-center gap-1 md:flex">
                    <a href="{{ route('platform.groups.index') }}"
                        @class([
                            'rounded-md px-2.5 py-1.5 text-sm',
                            'bg-brand-800 font-medium text-white' => request()->routeIs('platform.groups.*'),
                            'text-brand-100 hover:bg-brand-800/60' => !request()->routeIs('platform.groups.*'),
                        ])>
                        Grupos
                    </a>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex h-9 items-center rounded-md border border-brand-700 px-3 text-sm font-medium text-brand-100 hover:bg-brand-800/60">
                        Meu painel
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="inline-flex h-9 items-center rounded-md border border-brand-700 px-3 text-sm font-medium text-brand-100 hover:bg-brand-800/60">
                            Sair
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 pb-10 pt-5 sm:px-6">
            @if (session('status'))
                <div
                    class="mb-4 rounded-md border border-success-500/40 bg-success-50 px-4 py-2.5 text-sm font-medium text-success-700">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('warning'))
                <div
                    class="mb-4 rounded-md border border-warning-500/40 bg-warning-50 px-4 py-2.5 text-sm font-medium text-warning-700">
                    {{ session('warning') }}
                </div>
            @endif

            @if ($errors->any())
                <div
                    class="mb-4 rounded-md border border-danger-500/40 bg-danger-50 px-4 py-2.5 text-sm font-medium text-danger-700">
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mx-auto w-full max-w-7xl">
                @yield('content')
            </div>
        </main>
    </div>
</body>

</html>
