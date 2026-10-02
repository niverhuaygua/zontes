<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Domain\Customers\BrandMember;
use App\Domain\Customers\Enums\MemberStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Resuelve a que marca entrar el usuario autenticado: la que tenga activa en
 * sesion, o su primera membresia activa.
 */
class PortalEntryController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $member = BrandMember::withoutGlobalScopes()
            ->where('user_id', $user->getKey())
            ->where('status', MemberStatus::Active->value)
            ->with('brand')
            ->first();

        if ($member === null || $member->brand === null) {
            return redirect()->route('home')
                ->with('status', 'Aun no perteneces a ninguna marca. Elige una para registrarte.');
        }

        return redirect()->route('portal.home', ['brand' => $member->brand->slug]);
    }
}
