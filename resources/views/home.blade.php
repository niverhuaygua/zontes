@extends('layouts.app')

@section('title', 'Elige tu marca')

@section('content')
    <section class="rounded-lg bg-white p-6 shadow">
        <h1 class="text-2xl font-bold brand-text">Programa de fidelizacion</h1>
        <p class="mt-2 text-gray-600">
            Acumula y consulta tus puntos de cada marca por separado. Los puntos son independientes:
            los de una marca nunca se suman ni se canjean en otra.
        </p>

        <h2 class="mt-6 text-lg font-semibold">Marcas disponibles</h2>

        @if ($brands->isEmpty())
            <p class="mt-2 text-gray-500">Todavia no hay marcas activas. Ejecuta <code>php artisan migrate --seed</code>.</p>
        @else
            <ul class="mt-4 grid gap-4 sm:grid-cols-3">
                @foreach ($brands as $brand)
                    <li class="rounded-lg border p-4" style="border-color: {{ $brand->themeVariables()['--brand-primary'] }}">
                        <p class="text-lg font-semibold" style="color: {{ $brand->themeVariables()['--brand-primary'] }}">
                            {{ $brand->name }}
                        </p>
                        <p class="text-sm text-gray-500">{{ $brand->currency }}</p>
                        <div class="mt-3 flex flex-wrap gap-2 text-sm">
                            <a class="rounded px-3 py-1 text-white" style="background-color: {{ $brand->themeVariables()['--brand-primary'] }}"
                               href="{{ route('portal.home', ['brand' => $brand->slug]) }}">
                                Entrar
                            </a>
                            <a class="rounded border px-3 py-1"
                               href="{{ route('register', ['brand' => $brand->slug]) }}">
                                Registrarme
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
