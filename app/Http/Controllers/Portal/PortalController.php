<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Domain\Brands\CurrentBrand;
use App\Domain\Customers\BrandMember;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function __construct(private readonly CurrentBrand $currentBrand) {}

    public function index(Request $request): View
    {
        $brand = $this->currentBrand->require();
        $member = null;

        if ($user = $request->user()) {
            $member = $this->memberFor($user, (int) $brand->getKey());
        }

        return view('portal.index', [
            'brand' => $brand,
            'member' => $member,
        ]);
    }

    private function memberFor(User $user, int $brandId): ?BrandMember
    {
        return BrandMember::withoutGlobalScopes()
            ->where('user_id', $user->getKey())
            ->where('brand_id', $brandId)
            ->with('wallet')
            ->first();
    }
}
