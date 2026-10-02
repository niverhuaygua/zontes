<div class="rounded-lg bg-white p-6 shadow">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm text-gray-500">Saldo disponible</p>
            <p class="text-3xl font-bold brand-text">{{ number_format($balance) }} pts</p>
            <p class="mt-1 text-sm text-gray-500">Miembro desde {{ $member->joined_at?->format('d/m/Y') ?? '-' }}</p>
        </div>
        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
            {{ $member->status->value }}
        </span>
    </div>

    @if ($expiring > 0)
        <p class="mt-4 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            Tienes <strong>{{ number_format($expiring) }} puntos</strong> que vencen en los proximos {{ $noticeDays }} dias.
        </p>
    @endif
</div>
