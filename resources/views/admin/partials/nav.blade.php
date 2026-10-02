@php
    $adminBrands = auth()->user()->isSuperAdmin()
        ? \App\Models\Brand::query()->orderBy('name')->get()
        : auth()->user()->administeredBrands()->orderBy('name')->get();
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-white px-4 py-3 shadow">
    <nav class="flex flex-wrap gap-4 text-sm" aria-label="Navegacion del panel">
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'brand-text font-semibold' : 'hover:underline' }}">Resumen</a>
        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'brand-text font-semibold' : 'hover:underline' }}">Usuarios</a>
        <a href="{{ route('admin.rules.index') }}" class="{{ request()->routeIs('admin.rules.*') ? 'brand-text font-semibold' : 'hover:underline' }}">Reglas de puntos</a>
        <a href="{{ route('admin.imports.index') }}" class="{{ request()->routeIs('admin.imports.*') ? 'brand-text font-semibold' : 'hover:underline' }}">Importaciones</a>
        <a href="{{ route('admin.ledger.index') }}" class="{{ request()->routeIs('admin.ledger.*') ? 'brand-text font-semibold' : 'hover:underline' }}">Movimientos</a>
        @if (auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.brands.index') }}" class="{{ request()->routeIs('admin.brands.*') ? 'brand-text font-semibold' : 'hover:underline' }}">Marcas</a>
        @endif
    </nav>

    @if ($adminBrands->count() > 1)
        <form method="POST" action="{{ route('admin.brand.switch') }}" class="flex items-center gap-2 text-sm">
            @csrf
            <label for="admin_brand_id" class="text-gray-600">Marca activa</label>
            <select id="admin_brand_id" name="brand_id" onchange="this.form.submit()" class="rounded border-gray-300">
                @foreach ($adminBrands as $option)
                    <option value="{{ $option->id }}" @selected(($currentBrand ?? null) && $currentBrand->id === $option->id)>
                        {{ $option->name }}
                    </option>
                @endforeach
            </select>
            <noscript><button type="submit" class="rounded border px-2 py-1">Cambiar</button></noscript>
        </form>
    @elseif (($currentBrand ?? null))
        <span class="text-sm text-gray-500">Marca activa: <strong>{{ $currentBrand->name }}</strong></span>
    @endif
</div>
