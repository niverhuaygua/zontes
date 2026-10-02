@extends('layouts.app')

@section('title', 'Importacion '.$import->filename)

@section('content')
    @include('admin.partials.nav')

    <div class="mt-6 rounded-lg bg-white p-6 shadow">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold">{{ $import->filename }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $import->type->value }} &middot; estado: <strong>{{ $import->status->value }}</strong>
                    &middot; {{ $import->created_at?->format('d/m/Y H:i') }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($import->errors->isNotEmpty())
                    <a href="{{ route('admin.imports.errors', $import) }}" class="rounded border px-3 py-1 text-sm">Descargar errores</a>
                @endif
                @if (! $import->isProcessed())
                    <form method="POST" action="{{ route('admin.imports.confirm', $import) }}">
                        @csrf
                        <button type="submit" class="brand-btn rounded px-3 py-1 text-sm">Confirmar y procesar</button>
                    </form>
                @endif
            </div>
        </div>

        @if ($import->error_message)
            <p class="mt-3 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $import->error_message }}</p>
        @endif

        <div class="mt-4 grid gap-3 sm:grid-cols-3 text-sm">
            <div class="rounded border p-3">Filas totales: <strong>{{ (int) $import->rows_total }}</strong></div>
            <div class="rounded border p-3 text-green-700">Correctas: <strong>{{ (int) $import->rows_ok }}</strong></div>
            <div class="rounded border p-3 text-red-700">Con error: <strong>{{ (int) $import->rows_failed }}</strong></div>
        </div>

        @if (! empty($import->preview))
            <h2 class="mt-6 font-semibold">Vista previa (primeras filas)</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            @foreach (array_keys($import->preview[0]) as $column)
                                <th class="px-2 py-1">{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($import->preview as $row)
                            <tr class="border-t">
                                @foreach ($row as $value)
                                    <td class="px-2 py-1">{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <h2 class="mt-6 font-semibold">Errores <span class="text-gray-400">({{ $import->errors->count() }})</span></h2>
        @if ($import->errors->isEmpty())
            <p class="mt-2 text-sm text-gray-500">Sin errores registrados.</p>
        @else
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-50 text-left text-gray-500">
                        <tr>
                            <th class="px-2 py-1">Fila</th>
                            <th class="px-2 py-1">CI</th>
                            <th class="px-2 py-1">Mensaje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($import->errors as $error)
                            <tr class="border-t">
                                <td class="px-2 py-1">{{ $error->row_number }}</td>
                                <td class="px-2 py-1">{{ $error->ci }}</td>
                                <td class="px-2 py-1">{{ $error->message }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="mt-6">
            <a href="{{ route('admin.imports.index') }}" class="text-sm brand-text underline">&larr; Volver a importaciones</a>
        </div>
    </div>
@endsection
