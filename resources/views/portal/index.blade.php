@extends('layouts.app')

@section('title', 'Mi cuenta en '.$brand->name)

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold brand-text">{{ $brand->name }}</h1>
    </div>

    @guest
        <div class="mt-4 rounded-lg border border-dashed border-gray-300 bg-white p-4 text-sm text-gray-600">
            <p>Inicia sesion o registrate para ver tu saldo y tus puntos.</p>
            <div class="mt-3 flex gap-2">
                <a href="{{ route('login') }}" class="brand-btn rounded px-3 py-1 text-sm">Entrar</a>
                <a href="{{ route('register', ['brand' => $brand->slug]) }}" class="rounded border px-3 py-1 text-sm">Registrarme</a>
            </div>
        </div>
    @endguest

    @auth
        @if ($member)
            <div class="mt-4 grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 space-y-6">
                    <livewire:portal.wallet-summary :memberId="$member->id" />
                    <livewire:portal.expiring-lots :memberId="$member->id" />
                    <livewire:portal.points-history :memberId="$member->id" />
                </div>
                <div class="space-y-6">
                    <livewire:portal.referral-panel :memberId="$member->id" />
                </div>
            </div>
        @else
            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                Aun no perteneces a {{ $brand->name }}. Este prototipo enlaza tu cuenta al registrarte en la marca;
                si acabas de registrarte y no ves tu saldo, verifica tu correo o contacta al administrador.
            </div>
        @endif
    @endauth
@endsection
