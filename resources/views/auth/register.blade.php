@extends('layouts.app')

@section('title', 'Registro en '.$brand->name)

@section('content')
    <div class="mx-auto max-w-lg rounded-lg bg-white p-6 shadow">
        <h1 class="text-xl font-bold brand-text">Crear cuenta en {{ $brand->name }}</h1>
        <p class="mt-1 text-sm text-gray-600">
            Usa tu cedula de identidad para vincular tus compras y mantenimientos anteriores.
        </p>

        @if ($ref)
            <p class="mt-3 rounded border border-amber-200 bg-amber-50 p-2 text-sm text-amber-800">
                Te registras con el codigo de referido <strong>{{ $ref }}</strong>.
            </p>
        @endif

        <form method="POST" action="{{ route('register.store', ['brand' => $brand->slug]) }}" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="ref" value="{{ old('ref', $ref) }}">

            <div>
                <label for="name" class="block text-sm font-medium">Nombre completo</label>
                <input id="name" name="name" type="text" required value="{{ old('name') }}"
                       class="mt-1 w-full rounded border-gray-300">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="ci" class="block text-sm font-medium">Cedula de identidad</label>
                    <input id="ci" name="ci" type="text" required value="{{ old('ci') }}"
                           class="mt-1 w-full rounded border-gray-300">
                    @error('ci') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium">Telefono (opcional)</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                           class="mt-1 w-full rounded border-gray-300">
                    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium">Correo electronico</label>
                <input id="email" name="email" type="email" required value="{{ old('email') }}"
                       class="mt-1 w-full rounded border-gray-300">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="birth_date" class="block text-sm font-medium">Fecha de nacimiento (opcional)</label>
                    <input id="birth_date" name="birth_date" type="date" value="{{ old('birth_date') }}"
                           class="mt-1 w-full rounded border-gray-300">
                    @error('birth_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium">Contrasena</label>
                    <input id="password" name="password" type="password" required
                           class="mt-1 w-full rounded border-gray-300">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium">Repite la contrasena</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="mt-1 w-full rounded border-gray-300">
            </div>

            <p class="text-xs text-gray-500">
                Al registrarte aceptas el tratamiento de tus datos para el programa de fidelizacion.
            </p>

            <button type="submit" class="brand-btn w-full rounded px-4 py-2 font-semibold">
                Crear mi cuenta
            </button>
        </form>
    </div>
@endsection
