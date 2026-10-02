@extends('layouts.app')

@section('title', 'Usuario')

@section('content')
    @include('admin.partials.nav')

    <div class="mt-6 rounded-lg bg-white p-6 shadow">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold">{{ $user->name }}</h1>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                <p class="mt-1 text-sm">
                    Rol: <span class="rounded bg-gray-100 px-2 py-0.5 text-xs">{{ $user->role->value }}</span>
                    @if ($user->ci) &middot; CI: {{ $user->ci }} @endif
                </p>
            </div>
            <a href="{{ route('admin.users.edit', $user) }}" class="brand-btn rounded px-3 py-1 text-sm">Editar</a>
        </div>

        <h2 class="mt-6 font-semibold">Membresias por marca</h2>

        @if ($members->isEmpty())
            <p class="mt-2 text-sm text-gray-500">Este usuario aun no pertenece a ninguna marca.</p>
        @else
            <table class="mt-3 w-full text-sm">
                <thead class="text-left text-gray-500">
                    <tr>
                        <th class="py-1">Marca</th>
                        <th class="py-1">Estado</th>
                        <th class="py-1">Codigo de referido</th>
                        <th class="py-1 text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $member)
                        <tr class="border-t">
                            <td class="py-2">{{ $member->brand?->name ?? '-' }}</td>
                            <td class="py-2">{{ $member->status->value }}</td>
                            <td class="py-2 font-mono">{{ $member->referral_code }}</td>
                            <td class="py-2 text-right">{{ number_format($member->balance()) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
