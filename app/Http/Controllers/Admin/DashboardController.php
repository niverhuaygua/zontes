<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Brands\CurrentBrand;
use App\Domain\Customers\BrandMember;
use App\Domain\Customers\CustomerRecord;
use App\Domain\Imports\CsvImport;
use App\Domain\Loyalty\Enums\LedgerType;
use App\Domain\Loyalty\PointLedger;
use App\Domain\Loyalty\PointRule;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly CurrentBrand $currentBrand) {}

    public function index(): View
    {
        $brand = $this->currentBrand->get();
        $brandId = $brand?->getKey();

        $memberIds = $brandId === null
            ? []
            : BrandMember::withoutGlobalScopes()->where('brand_id', $brandId)->pluck('id');

        $ledger = PointLedger::withoutGlobalScopes()->whereIn('brand_member_id', $memberIds);

        $stats = [
            'members' => count($memberIds),
            'rules' => $brandId === null ? 0 : PointRule::withoutGlobalScopes()->where('brand_id', $brandId)->where('active', true)->count(),
            'issued' => (int) (clone $ledger)->where('type', LedgerType::Earn->value)->sum('points'),
            'redeemed' => (int) abs((clone $ledger)->where('type', LedgerType::Redeem->value)->sum('points')),
            'expired' => (int) abs((clone $ledger)->where('type', LedgerType::Expire->value)->sum('points')),
            'balance' => (int) (clone $ledger)->whereNotNull('lot_remaining')->sum('lot_remaining'),
            'unclaimed' => $brandId === null ? 0 : CustomerRecord::withoutGlobalScopes()->where('brand_id', $brandId)->where('claim_status', 'unclaimed')->count(),
            'imports' => $brandId === null ? 0 : CsvImport::withoutGlobalScopes()->where('brand_id', $brandId)->count(),
        ];

        return view('admin.dashboard', [
            'brand' => $brand,
            'stats' => $stats,
            'recentImports' => $brandId === null
                ? collect()
                : CsvImport::withoutGlobalScopes()->where('brand_id', $brandId)->latest()->limit(5)->get(),
        ]);
    }
}
