@extends('layouts.app')

@section('title', $brand->exists ? 'Editar marca' : 'Nueva marca')

@section('content')
    @include('admin.partials.nav')

    @php($theme = $brand->themeVariables())

    <div class="mx-auto mt-6 max-w-2xl rounded-lg bg-white p-6 shadow">
        <h1 class="text-xl font-bold">{{ $brand->exists ? 'Editar '.$brand->name : 'Nueva marca' }}</h1>

        <form method="POST"
              action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}"
              class="mt-4 space-y-4">
            @csrf
            @if ($brand->exists)
                @method('PUT')
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium">Nombre</label>
                    <input id="name" name="name" type="text" required value="{{ old('name', $brand->name) }}"
                           class="mt-1 w-full rounded border-gray-300">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="short_name" class="block text-sm font-medium">Nombre corto</label>
                    <input id="short_name" name="short_name" type="text" value="{{ old('short_name', $brand->short_name) }}"
                           class="mt-1 w-full rounded border-gray-300">
                    @error('short_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="slug" class="block text-sm font-medium">Slug (subdominio / ruta)</label>
                <input id="slug" name="slug" type="text" required value="{{ old('slug', $brand->slug) }}"
                       @disabled($brand->exists)
                       class="mt-1 w-full rounded border-gray-300 disabled:bg-gray-100">
                @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                @foreach (['primary' => 'Color principal', 'primary_dark' => 'Principal oscuro', 'accent' => 'Acento'] as $field => $label)
                    @php($cssVar = '--brand-'.str_replace('_', '-', $field))
                    <div>
                        <label for="{{ $field }}" class="block text-sm font-medium">{{ $label }}</label>
                        <input id="{{ $field }}" name="{{ $field }}" type="color"
                               value="{{ old($field, $theme[$cssVar] ?? '#000000') }}"
                               class="mt-1 h-10 w-full rounded border-gray-300">
                        @error($field) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="active" value="1" @checked(old('active', $brand->active ?? true))>
                Marca activa (visible en el portal)
            </label>

            <div class="flex gap-2">
                <button type="submit" class="brand-btn rounded px-4 py-2 font-semibold">Guardar</button>
                <a href="{{ route('admin.brands.index') }}" class="rounded border px-4 py-2">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
