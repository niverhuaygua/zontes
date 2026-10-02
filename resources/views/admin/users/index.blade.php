@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
    @include('admin.partials.nav')

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold">Usuarios</h1>

        <form method="GET" class="flex flex-wrap items-end gap-2 text-sm">
            <div>
                <label for="q" class="block text-gray-600">Buscar</label>
                <input id="q" name="q" type="text" value="{{ request('q') }}" class="rounded border-gray-300">
            </div>
            <div>
                <label for="role" class="block text-gray-600">Rol</label>
                <select id="role" name="role" class="rounded border-gray-300">
                    <option value="">Todos</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->value }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded border px-3 py-1">Filtrar</button>
        </form>
    </div>

    <div class="mt-4 overflow-x-auto rounded-lg bg-white shadow">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500">
                <tr>
                    <th class="px-4 py-2">Nombre</th>
                    <th class="px-4 py-2">Correo</th>
                    <th class="px-4 py-2">Rol</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="border-t">
                        <td class="px-4 py-2 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-2">{{ $user->email }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs">{{ $user->role->value }}</span>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <a class="brand-text underline" href="{{ route('admin.users.show', $user) }}">Ver</a>
                            <a class="ml-3 brand-text underline" href="{{ route('admin.users.edit', $user) }}">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-4 text-center text-gray-500">No se encontraron usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
