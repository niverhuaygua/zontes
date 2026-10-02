<div class="rounded-lg bg-white p-6 shadow">
    <h2 class="font-semibold">Invita y gana</h2>
    <p class="mt-1 text-sm text-gray-500">Comparte tu codigo. Ganas puntos cuando tu referido se registra y cuando hace su primera compra.</p>

    <div class="mt-4 rounded border border-dashed border-gray-300 p-3 text-center">
        <p class="text-xs text-gray-500">Tu codigo</p>
        <p class="text-2xl font-bold tracking-widest brand-text">{{ $code }}</p>
    </div>

    <label for="referral-link" class="mt-4 block text-sm font-medium">Enlace para compartir</label>
    <div class="mt-1 flex gap-2">
        <input id="referral-link" type="text" readonly value="{{ $link }}"
               class="w-full rounded border-gray-300 bg-gray-50 text-xs" onclick="this.select()">
    </div>

    <div class="mt-4 grid grid-cols-2 gap-3 text-center text-sm">
        <div class="rounded border p-3">
            <p class="text-xl font-bold">{{ number_format($registered) }}</p>
            <p class="text-gray-500">Registrados</p>
        </div>
        <div class="rounded border p-3">
            <p class="text-xl font-bold">{{ number_format($purchased) }}</p>
            <p class="text-gray-500">Con primera compra</p>
        </div>
    </div>
</div>
