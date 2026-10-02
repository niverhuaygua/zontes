@extends('layouts.app')

@section('title', 'Reglas de puntos')

@section('content')
    @include('admin.partials.nav')

    <div class="mt-6 flex items-center justify-between">
        <h1 class="text-xl font-bold">Reglas de puntos &middot; {{ $brand->name }}</h1>
        <a href="{{ route('admin.rules.create') }}" class="brand-btn rounded px-3 py-1 text-sm">Nueva regla</a>
    </div>

    <div class="mt-4 overflow-x-auto rounded-lg bg-white shadow">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500">
                <tr>
                    <th class="px-4 py-2">Nombre</th>
                    <th class="px-4 py-2">Evento</th>
                    <th class="px-4 py-2">Calculo</th>
                    <th class="px-4 py-2">Valor</th>
                    <th class="px-4 py-2">Vence (dias)</th>
                    <th class="px-4 py-2">Vigencia</th>
                    <th class="px-4 py-2">Estado</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rules as $rule)
                    <tr class="border-t">
                        <td class="px-4 py-2 font-medium">{{ $rule->name }}</td>
                        <td class="px-4 py-2">{{ $rule->event_type->value }}</td>
                        <td class="px-4 py-2">{{ $rule->calc_type->value }}</td>
                        <td class="px-4 py-2">{{ $rule->value }}</td>
                        <td class="px-4 py-2">{{ $rule->expires_after_days ?? 'Sin vencimiento' }}</td>
                        <td class="px-4 py-2 text-xs text-gray-500">
                            {{ $rule->starts_at?->format('d/m/Y') ?? 'Siempre' }} &rarr; {{ $rule->ends_at?->format('d/m/Y') ?? 'Sin fin' }}
                        </td>
                        <td class="px-4 py-2">{{ $rule->active ? 'Activa' : 'Inactiva' }}</td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <a class="brand-text underline" href="{{ route('admin.rules.edit', $rule) }}">Editar</a>
                            <form method="POST" action="{{ route('admin.rules.destroy', $rule) }}" class="inline"
                                  onsubmit="return confirm('¿Eliminar esta regla?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ml-3 text-red-600 underline">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-4 text-center text-gray-500">No hay reglas para esta marca.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
