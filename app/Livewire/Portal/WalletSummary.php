<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Domain\Customers\BrandMember;
use App\Domain\Loyalty\Enums\LedgerType;
use App\Domain\Loyalty\PointLedger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Resumen de la billetera: saldo, puntos por vencer y datos de la membresia.
 */
class WalletSummary extends Component
{
    #[Locked]
    public int $memberId;

    public function mount(int $memberId): void
    {
        $this->memberId = $this->authorizeMember($memberId)->getKey();
    }

    public function render(): View
    {
        $member = $this->authorizeMember($this->memberId);
        $noticeDays = (int) config('loyalty.points.expiring_notice_days', 30);

        $expiring = (int) PointLedger::withoutGlobalScopes()
            ->where('brand_member_id', $member->getKey())
            ->where('type', LedgerType::Earn->value)
            ->where('lot_remaining', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', Carbon::now()->addDays($noticeDays))
            ->sum('lot_remaining');

        return view('livewire.portal.wallet-summary', [
            'member' => $member,
            'balance' => $member->balance(),
            'expiring' => $expiring,
            'noticeDays' => $noticeDays,
        ]);
    }

    private function authorizeMember(int $memberId): BrandMember
    {
        $member = BrandMember::withoutGlobalScopes()
            ->whereKey($memberId)
            ->where('user_id', Auth::id())
            ->with('brand')
            ->first();

        abort_if($member === null, 403, 'No tienes acceso a esta membresia.');

        return $member;
    }
}
