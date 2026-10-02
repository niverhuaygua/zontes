<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Programa de fidelizacion') - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            @foreach (($brandThemeCss ?? []) as $var => $value)
                {{ $var }}: {{ $value }};
            @endforeach
        }
        .brand-bg { background-color: var(--brand-primary, #b91c1c); }
        .brand-text { color: var(--brand-primary, #b91c1c); }
        .brand-btn { background-color: var(--brand-primary, #b91c1c); color: #fff; }
        .brand-btn:hover { filter: brightness(.9); }
        .brand-accent { color: var(--brand-accent, #f59e0b); }
        :focus-visible { outline: 2px solid var(--brand-accent, #f59e0b); outline-offset: 2px; }
    </style>
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:bg-white focus:p-2">Saltar al contenido</a>

    <header class="brand-bg text-white shadow">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-bold">
                @if (($currentBrand ?? null) && $currentBrand->logo_path)
                    <img src="{{ Storage::url($currentBrand->logo_path) }}" alt="Logo {{ $currentBrand->name }}" class="h-8">
                @endif
                <span>{{ config('app.name') }}@if (($currentBrand ?? null)) &middot; {{ $currentBrand->name }}@endif</span>
            </a>

            <nav class="flex flex-wrap items-center gap-4 text-sm" aria-label="Navegacion principal">
                @auth
                    @if (auth()->user()->isAdmin())
                        <a class="hover:underline" href="{{ route('admin.dashboard') }}">Panel admin</a>
                    @endif
                    <a class="hover:underline" href="{{ route('portal.entry') }}">Mi cuenta</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="hover:underline">Salir</button>
                    </form>
                @else
                    <a class="hover:underline" href="{{ route('login') }}">Entrar</a>
                @endauth
            </nav>
        </div>
    </header>

    <div class="mx-auto max-w-6xl px-4">
        @if (session('status'))
            <div class="mt-4 rounded border border-green-200 bg-green-50 p-3 text-sm text-green-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800" role="alert">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <main id="contenido" class="mx-auto max-w-6xl px-4 py-6">
        @yield('content')
    </main>

    <footer class="mx-auto max-w-6xl px-4 py-8 text-xs text-gray-500">
        {{ config('app.name') }} &mdash; Programa de fidelizacion multimarca. Bolivia.
    </footer>

    @livewireScripts
</body>
</html>
