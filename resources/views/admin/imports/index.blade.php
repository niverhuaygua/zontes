@extends('layouts.app')

@section('title', 'Importaciones CSV')

@section('content')
    @include('admin.partials.nav')

    <div class="mt-6 flex items-center justify-between">
        <h1 class="text-xl font-bold">Importaciones CSV &middot; {{ $brand->name }}</h1>
        <a href="{{ route('admin.imports.create') }}" class="brand-btn rounded px-3 py-1 text-sm">Subir CSV</a>
    </div>

    <div class="mt-4 overflow-x-auto rounded-lg bg-white shadow">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500">
                <tr>
                    <th class="px-4 py-2">Archivo</th>
                    <th class="px-4 py-2">Tipo</th>
                    <th class="px-4 py-2">Estado</th>
                    <th class="px-4 py-2">OK / Error</th>
                    <th class="px-4 py-2">Fecha</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($imports as $import)
                    <tr class="border-t">
                        <td class="px-4 py-2 font-medium">{{ $import->filename }}</td>
                        <td class="px-4 py-2">{{ $import->type->value }}</td>
                        <td class="px-4 py-2">{{ $import->status->value }}</td>
                        <td class="px-4 py-2">{{ (int) $import->rows_ok }} / {{ (int) $import->rows_failed }}</td>
                        <td class="px-4 py-2">{{ $import->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2 text-right">
                            <a class="brand-text underline" href="{{ route('admin.imports.show', $import) }}">Ver</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-4 text-center text-gray-500">Todavia no hay importaciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $imports->links() }}</div>
@endsection
