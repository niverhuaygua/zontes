<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Domain\Customers\BrandMember;
use App\Domain\Loyalty\PointLedger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Historial de movimientos de puntos de la membresia (solo lectura).
 */
class PointsHistory extends Component
{
    use WithPagination;

    #[Locked]
    public int $memberId;

    #[Url(as: 'tipo')]
    public string $type = '';

    public function mount(int $memberId): void
    {
        $this->memberId = $this->authorizeMember($memberId)->getKey();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $member = $this->authorizeMember($this->memberId);

        $entries = PointLedger::withoutGlobalScopes()
            ->where('brand_member_id', $member->getKey())
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.portal.points-history', [
            'entries' => $entries,
        ]);
    }

    private function authorizeMember(int $memberId): BrandMember
    {
        $member = BrandMember::withoutGlobalScopes()
            ->whereKey($memberId)
            ->where('user_id', Auth::id())
            ->first();

        abort_if($member === null, 403, 'No tienes acceso a esta membresia.');

        return $member;
    }
}
