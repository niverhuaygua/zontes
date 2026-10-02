<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Brands\CurrentBrand;
use App\Domain\Customers\BrandMember;
use App\Domain\Loyalty\Actions\AdjustPoints;
use App\Domain\Loyalty\Enums\LedgerType;
use App\Domain\Loyalty\PointLedger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustPointsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LedgerController extends Controller
{
    public function __construct(private readonly CurrentBrand $currentBrand) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', PointLedger::class);
        $brand = $this->currentBrand->require();

        $memberIds = BrandMember::withoutGlobalScopes()
            ->where('brand_id', $brand->getKey())
            ->pluck('id');

        $entries = PointLedger::withoutGlobalScopes()
            ->whereIn('brand_member_id', $memberIds)
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->with(['brandMember.user', 'rule'])
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.ledger.index', [
            'brand' => $brand,
            'entries' => $entries,
            'types' => LedgerType::cases(),
            'members' => BrandMember::withoutGlobalScopes()
                ->where('brand_id', $brand->getKey())
                ->with('user')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function adjust(AdjustPointsRequest $request, AdjustPoints $adjustPoints): RedirectResponse
    {
        $brand = $this->currentBrand->require();

        $member = BrandMember::withoutGlobalScopes()
            ->whereKey($request->integer('brand_member_id'))
            ->where('brand_id', $brand->getKey())
            ->firstOrFail();

        $adjustPoints->handle(
            member: $member,
            points: $request->integer('points'),
            note: (string) $request->string('note'),
            createdBy: (int) $request->user()->getKey(),
        );

        return redirect()->route('admin.ledger.index')->with('status', 'Ajuste registrado.');
    }
}
