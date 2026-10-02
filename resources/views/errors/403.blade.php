@extends('layouts.app')

@section('title', 'Acceso denegado')

@section('content')
    <div class="mx-auto max-w-lg rounded-lg bg-white p-8 text-center shadow">
        <p class="text-5xl font-bold brand-text">403</p>
        <h1 class="mt-2 text-xl font-bold">No tienes permiso para ver esta pagina</h1>
        <p class="mt-2 text-sm text-gray-600">
            Si crees que es un error, contacta al administrador del programa.
        </p>
        <a href="{{ route('home') }}" class="brand-btn mt-6 inline-block rounded px-4 py-2 text-sm font-semibold">
            Volver al inicio
        </a>
    </div>
@endsection
