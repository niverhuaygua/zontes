@extends('layouts.app')

@section('title', 'Editar usuario')

@section('content')
    @include('admin.partials.nav')

    <div class="mx-auto mt-6 max-w-2xl rounded-lg bg-white p-6 shadow">
        <h1 class="text-xl font-bold">Editar usuario</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $user->email }}</p>

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-4 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium">Nombre</label>
                <input id="name" name="name" type="text" required value="{{ old('name', $user->name) }}"
                       class="mt-1 w-full rounded border-gray-300">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="role" class="block text-sm font-medium">Rol</label>
                <select id="role" name="role" class="mt-1 w-full rounded border-gray-300">
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('role', $user->role->value) === $role->value)>
                            {{ $role->value }}
                        </option>
                    @endforeach
                </select>
                @error('role') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <fieldset class="rounded border p-3">
                <legend class="px-1 text-sm font-medium">Marcas que administra (solo brand_admin)</legend>
                @php($selectedBrands = old('brands', $user->administeredBrands->pluck('id')->all()))
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($brands as $brand)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="brands[]" value="{{ $brand->id }}"
                                   @checked(in_array($brand->id, $selectedBrands, true))>
                            {{ $brand->name }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="flex gap-2">
                <button type="submit" class="brand-btn rounded px-4 py-2 font-semibold">Guardar</button>
                <a href="{{ route('admin.users.index') }}" class="rounded border px-4 py-2">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
