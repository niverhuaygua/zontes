<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Domain\Customers\BrandMember;
use App\Domain\Referrals\Enums\ReferralStatus;
use App\Domain\Referrals\Referral;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Codigo de referido y enlace para compartir.
 */
class ReferralPanel extends Component
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

        $registered = Referral::withoutGlobalScopes()
            ->where('referrer_member_id', $member->getKey())
            ->count();

        $purchased = Referral::withoutGlobalScopes()
            ->where('referrer_member_id', $member->getKey())
            ->where('status', ReferralStatus::Purchased->value)
            ->count();

        return view('livewire.portal.referral-panel', [
            'member' => $member,
            'code' => $member->referral_code,
            'link' => route('register', ['brand' => $member->brand?->slug, 'ref' => $member->referral_code]),
            'registered' => $registered,
            'purchased' => $purchased,
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
