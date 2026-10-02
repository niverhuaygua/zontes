@extends('layouts.app')

@section('title', 'Movimientos de puntos')

@section('content')
    @include('admin.partials.nav')

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-bold">Movimientos &middot; {{ $brand->name }}</h1>

        <form method="GET" class="flex items-end gap-2 text-sm">
            <div>
                <label for="type" class="block text-gray-600">Tipo</label>
                <select id="type" name="type" class="rounded border-gray-300">
                    <option value="">Todos</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->value }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded border px-3 py-1">Filtrar</button>
        </form>
    </div>

    <div class="mt-4 rounded-lg bg-white p-4 shadow">
        <h2 class="font-semibold">Ajuste manual</h2>
        <p class="text-xs text-gray-500">
            Los movimientos son inmutables: un ajuste crea un nuevo movimiento con motivo obligatorio y queda auditado.
        </p>

        <form method="POST" action="{{ route('admin.ledger.adjust') }}" class="mt-3 grid gap-3 sm:grid-cols-4 sm:items-end">
            @csrf
            <div class="sm:col-span-2">
                <label for="brand_member_id" class="block text-sm font-medium">Cliente</label>
                <select id="brand_member_id" name="brand_member_id" class="mt-1 w-full rounded border-gray-300" required>
                    <option value="">Selecciona un cliente</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}" @selected(old('brand_member_id') == $member->id)>
                            {{ $member->user?->name }} &middot; {{ $member->referral_code }}
                        </option>
                    @endforeach
                </select>
                @error('brand_member_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="points" class="block text-sm font-medium">Puntos (+/-)</label>
                <input id="points" name="points" type="number" step="1" required value="{{ old('points') }}"
                       class="mt-1 w-full rounded border-gray-300">
                @error('points') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="note" class="block text-sm font-medium">Motivo</label>
                <input id="note" name="note" type="text" required minlength="5" value="{{ old('note') }}"
                       class="mt-1 w-full rounded border-gray-300">
                @error('note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-4">
                <button type="submit" class="brand-btn rounded px-4 py-2 text-sm font-semibold">Registrar ajuste</button>
            </div>
        </form>
    </div>

    <div class="mt-4 overflow-x-auto rounded-lg bg-white shadow">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500">
                <tr>
                    <th class="px-4 py-2">#</th>
                    <th class="px-4 py-2">Cliente</th>
                    <th class="px-4 py-2">Tipo</th>
                    <th class="px-4 py-2 text-right">Puntos</th>
                    <th class="px-4 py-2 text-right">Restante lote</th>
                    <th class="px-4 py-2">Vence</th>
                    <th class="px-4 py-2">Nota</th>
                    <th class="px-4 py-2">Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr class="border-t">
                        <td class="px-4 py-2 text-gray-400">{{ $entry->id }}</td>
                        <td class="px-4 py-2">{{ $entry->brandMember?->user?->name ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $entry->type->value }}</td>
                        <td class="px-4 py-2 text-right {{ $entry->points < 0 ? 'text-red-600' : 'text-green-700' }}">
                            {{ $entry->points > 0 ? '+' : '' }}{{ $entry->points }}
                        </td>
                        <td class="px-4 py-2 text-right">{{ $entry->lot_remaining === null ? '-' : $entry->lot_remaining }}</td>
                        <td class="px-4 py-2">{{ $entry->expires_at?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $entry->note }}</td>
                        <td class="px-4 py-2">{{ $entry->created_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-4 text-center text-gray-500">No hay movimientos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $entries->links() }}</div>
@endsection
