<div class="rounded-lg bg-white p-6 shadow">
    <h2 class="font-semibold">Puntos por vencer</h2>
    <p class="text-xs text-gray-500">Lotes que expiran en los proximos {{ $noticeDays }} dias.</p>

    @if ($lots->isEmpty())
        <p class="mt-3 text-sm text-gray-500">No tienes puntos proximos a vencer. Todo en orden.</p>
    @else
        <ul class="mt-3 divide-y">
            @foreach ($lots as $lot)
                <li class="flex items-center justify-between py-2 text-sm">
                    <span>Vence el {{ $lot->expires_at?->format('d/m/Y') }}</span>
                    <span class="font-semibold text-amber-700">{{ number_format((int) $lot->lot_remaining) }} pts</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
