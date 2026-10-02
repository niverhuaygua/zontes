<div class="rounded-lg bg-white p-6 shadow">
    <div class="flex items-center justify-between">
        <h2 class="font-semibold">Historial de puntos</h2>
        <select wire:model.live="type" class="rounded border-gray-300 text-sm">
            <option value="">Todos</option>
            @foreach (\App\Domain\Loyalty\Enums\LedgerType::cases() as $t)
                <option value="{{ $t->value }}">{{ $t->value }}</option>
            @endforeach
        </select>
    </div>

    @if ($entries->isEmpty())
        <p class="mt-3 text-sm text-gray-500">Aun no tienes movimientos de puntos.</p>
    @else
        <table class="mt-3 w-full text-sm">
            <thead class="text-left text-gray-500">
                <tr>
                    <th class="py-1">Fecha</th>
                    <th class="py-1">Tipo</th>
                    <th class="py-1">Detalle</th>
                    <th class="py-1 text-right">Puntos</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($entries as $entry)
                    <tr class="border-t">
                        <td class="py-2">{{ $entry->created_at?->format('d/m/Y') }}</td>
                        <td class="py-2">{{ $entry->type->value }}</td>
                        <td class="py-2">{{ $entry->note ?? ($entry->expires_at ? 'Vence '.$entry->expires_at->format('d/m/Y') : '-') }}</td>
                        <td class="py-2 text-right {{ $entry->points < 0 ? 'text-red-600' : 'text-green-700' }}">
                            {{ $entry->points > 0 ? '+' : '' }}{{ number_format($entry->points) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-3">{{ $entries->links() }}</div>
    @endif
</div>
