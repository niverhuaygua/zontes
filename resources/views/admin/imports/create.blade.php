@extends('layouts.app')

@section('title', 'Subir CSV')

@section('content')
    @include('admin.partials.nav')

    <div class="mx-auto mt-6 max-w-xl rounded-lg bg-white p-6 shadow">
        <h1 class="text-xl font-bold">Subir CSV &middot; {{ $brand->name }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            Codificacion UTF-8, separador <code>,</code> o <code>;</code> autodetectado, primera fila con encabezados.
        </p>

        <form method="POST" action="{{ route('admin.imports.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
            @csrf

            <div>
                <label for="type" class="block text-sm font-medium">Tipo de archivo</label>
                <select id="type" name="type" class="mt-1 w-full rounded border-gray-300">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->value }}</option>
                    @endforeach
                </select>
                @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="file" class="block text-sm font-medium">Archivo</label>
                <input id="file" name="file" type="file" accept=".csv,.txt" required
                       class="mt-1 w-full rounded border-gray-300">
                @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="rounded border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600">
                <p class="font-semibold">Columnas esperadas</p>
                <ul class="mt-1 list-disc pl-5">
                    <li><code>customers</code>: ci, nombres, apellidos, email, telefono, fecha_registro</li>
                    <li><code>purchases</code>: ci, numero_factura, fecha, monto, vin(opcional), modelo(opcional)</li>
                    <li><code>maintenances</code>: ci, numero_orden, fecha, tipo, vin(opcional)</li>
                </ul>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="brand-btn rounded px-4 py-2 font-semibold">Cargar y ver vista previa</button>
                <a href="{{ route('admin.imports.index') }}" class="rounded border px-4 py-2">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
