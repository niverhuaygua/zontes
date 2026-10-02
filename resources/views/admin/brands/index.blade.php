@extends('layouts.app')

@section('title', 'Marcas')

@section('content')
    @include('admin.partials.nav')

    <div class="mt-6 flex items-center justify-between">
        <h1 class="text-xl font-bold">Marcas</h1>
        <a href="{{ route('admin.brands.create') }}" class="brand-btn rounded px-3 py-1 text-sm">Nueva marca</a>
    </div>

    <div class="mt-4 overflow-x-auto rounded-lg bg-white shadow">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500">
                <tr>
                    <th class="px-4 py-2">Marca</th>
                    <th class="px-4 py-2">Slug</th>
                    <th class="px-4 py-2">Tema</th>
                    <th class="px-4 py-2">Estado</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($brands as $brand)
                    <tr class="border-t">
                        <td class="px-4 py-2 font-medium">{{ $brand->name }}</td>
                        <td class="px-4 py-2">{{ $brand->slug }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex gap-1">
                                @foreach ($brand->themeVariables() as $value)
                                    <span class="inline-block h-5 w-5 rounded" style="background-color: {{ $value }}"></span>
                                @endforeach
                            </span>
                        </td>
                        <td class="px-4 py-2">{{ $brand->active ? 'Activa' : 'Inactiva' }}</td>
                        <td class="px-4 py-2 text-right">
                            <a class="brand-text underline" href="{{ route('admin.brands.edit', $brand) }}">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">No hay marcas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
