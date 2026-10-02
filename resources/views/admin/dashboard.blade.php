@extends('layouts.app')

@section('title', 'Panel administrativo')

@section('content')
    @include('admin.partials.nav')

    @if (! $brand)
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            No hay una marca activa. Crea una marca y asigna al menos un administrador.
        </div>
    @else
        <div class="mt-6 flex items-center justify-between">
            <h1 class="text-xl font-bold">Resumen &middot; {{ $brand->name }}</h1>
            <span class="text-sm text-gray-500">Moneda: {{ $brand->currency }}</span>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $cards = [
                    ['Miembros', number_format($stats['members']), 'brand-text'],
                    ['Saldo vigente', number_format($stats['balance']), 'text-green-700'],
                    ['Puntos emitidos', number_format($stats['issued']), 'text-blue-700'],
                    ['Puntos canjeados', number_format($stats['redeemed']), 'text-purple-700'],
                    ['Puntos vencidos', number_format($stats['expired']), 'text-red-700'],
                    ['Reglas activas', number_format($stats['rules']), 'text-gray-700'],
                    ['Clientes por reclamar', number_format($stats['unclaimed']), 'text-amber-700'],
                    ['Importaciones', number_format($stats['imports']), 'text-gray-700'],
                ];
            @endphp

            @foreach ($cards as [$label, $value, $color])
                <div class="rounded-lg bg-white p-4 shadow">
                    <p class="text-sm text-gray-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-bold {{ $color }}">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 rounded-lg bg-white p-4 shadow">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">Ultimas importaciones</h2>
                <a href="{{ route('admin.imports.index') }}" class="text-sm brand-text underline">Ver todas</a>
            </div>

            @if ($recentImports->isEmpty())
                <p class="mt-2 text-sm text-gray-500">Todavia no se cargaron archivos CSV.</p>
            @else
                <table class="mt-3 w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500">
                            <th class="py-1">Archivo</th>
                            <th class="py-1">Tipo</th>
                            <th class="py-1">Estado</th>
                            <th class="py-1">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentImports as $import)
                            <tr class="border-t">
                                <td class="py-1">
                                    <a class="brand-text underline" href="{{ route('admin.imports.show', $import) }}">{{ $import->filename }}</a>
                                </td>
                                <td class="py-1">{{ $import->type->value }}</td>
                                <td class="py-1">{{ $import->status->value }}</td>
                                <td class="py-1">{{ $import->created_at?->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif
@endsection
