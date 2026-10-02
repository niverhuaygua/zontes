@extends('layouts.app')

@section('title', 'Entrar')

@section('content')
    <div class="mx-auto max-w-md rounded-lg bg-white p-6 shadow">
        <h1 class="text-xl font-bold brand-text">Entrar</h1>

        <form method="POST" action="{{ route('login.store') }}" class="mt-4 space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium">Correo electronico</label>
                <input id="email" name="email" type="email" required autofocus autocomplete="username"
                       value="{{ old('email') }}"
                       class="mt-1 w-full rounded border-gray-300 focus:border-gray-500 focus:ring-0">
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">Contrasena</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                       class="mt-1 w-full rounded border-gray-300 focus:border-gray-500 focus:ring-0">
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" value="1" class="rounded border-gray-300">
                Recordarme
            </label>

            <button type="submit" class="brand-btn w-full rounded px-4 py-2 font-semibold">
                Entrar
            </button>
        </form>

        <p class="mt-4 text-sm text-gray-600">
            &iquest;No tienes cuenta? Elige tu marca en
            <a class="brand-text underline" href="{{ route('home') }}">la pagina de inicio</a> y registrate.
        </p>
    </div>
@endsection
