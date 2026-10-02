@extends('layouts.app')

@section('title', $rule->exists ? 'Editar regla' : 'Nueva regla')

@section('content')
    @include('admin.partials.nav')

    <div class="mx-auto mt-6 max-w-2xl rounded-lg bg-white p-6 shadow">
        <h1 class="text-xl font-bold">{{ $rule->exists ? 'Editar regla' : 'Nueva regla' }} &middot; {{ $brand->name }}</h1>

        <form method="POST"
              action="{{ $rule->exists ? route('admin.rules.update', $rule) : route('admin.rules.store') }}"
              class="mt-4 space-y-4">
            @csrf
            @if ($rule->exists)
                @method('PUT')
            @endif

            <div>
                <label for="name" class="block text-sm font-medium">Nombre</label>
                <input id="name" name="name" type="text" required value="{{ old('name', $rule->name) }}"
                       class="mt-1 w-full rounded border-gray-300" placeholder="Ej: 1 punto por cada Bs 10">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="event_type" class="block text-sm font-medium">Evento</label>
                    <select id="event_type" name="event_type" class="mt-1 w-full rounded border-gray-300">
                        @foreach (\App\Domain\Loyalty\Enums\RuleEventType::cases() as $event)
                            <option value="{{ $event->value }}" @selected(old('event_type', $rule->event_type?->value) === $event->value)>
                                {{ $event->value }}
                            </option>
                        @endforeach
                    </select>
                    @error('event_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="calc_type" class="block text-sm font-medium">Tipo de calculo</label>
                    <select id="calc_type" name="calc_type" class="mt-1 w-full rounded border-gray-300">
                        @foreach (\App\Domain\Loyalty\Enums\CalcType::cases() as $calc)
                            <option value="{{ $calc->value }}" @selected(old('calc_type', $rule->calc_type?->value) === $calc->value)>
                                {{ $calc->value }}
                            </option>
                        @endforeach
                    </select>
                    @error('calc_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label for="value" class="block text-sm font-medium">Valor</label>
                    <input id="value" name="value" type="number" step="0.01" min="0" required
                           value="{{ old('value', $rule->value) }}" class="mt-1 w-full rounded border-gray-300">
                    @error('value') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="min_amount" class="block text-sm font-medium">Monto minimo (Bs)</label>
                    <input id="min_amount" name="min_amount" type="number" step="0.01" min="0"
                           value="{{ old('min_amount', $rule->min_amount) }}" class="mt-1 w-full rounded border-gray-300">
                    @error('min_amount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="expires_after_days" class="block text-sm font-medium">Vence en (dias)</label>
                    <input id="expires_after_days" name="expires_after_days" type="number" min="1" max="3650"
                           value="{{ old('expires_after_days', $rule->expires_after_days) }}"
                           class="mt-1 w-full rounded border-gray-300" placeholder="Vacio = sin vencimiento">
                    @error('expires_after_days') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="starts_at" class="block text-sm font-medium">Inicia</label>
                    <input id="starts_at" name="starts_at" type="date"
                           value="{{ old('starts_at', $rule->starts_at?->format('Y-m-d')) }}"
                           class="mt-1 w-full rounded border-gray-300">
                    @error('starts_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ends_at" class="block text-sm font-medium">Termina</label>
                    <input id="ends_at" name="ends_at" type="date"
                           value="{{ old('ends_at', $rule->ends_at?->format('Y-m-d')) }}"
                           class="mt-1 w-full rounded border-gray-300">
                    @error('ends_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="active" value="1" @checked(old('active', $rule->active ?? true))>
                Regla activa
            </label>

            <div class="flex gap-2">
                <button type="submit" class="brand-btn rounded px-4 py-2 font-semibold">Guardar</button>
                <a href="{{ route('admin.rules.index') }}" class="rounded border px-4 py-2">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
