<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Brands\CurrentBrand;
use App\Domain\Loyalty\Enums\CalcType;
use App\Domain\Loyalty\Enums\RuleEventType;
use App\Domain\Loyalty\PointRule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePointRuleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PointRuleController extends Controller
{
    public function __construct(private readonly CurrentBrand $currentBrand) {}

    public function index(): View
    {
        $this->authorize('viewAny', PointRule::class);
        $brand = $this->currentBrand->require();

        return view('admin.rules.index', [
            'brand' => $brand,
            'rules' => PointRule::withoutGlobalScopes()
                ->where('brand_id', $brand->getKey())
                ->orderBy('event_type')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PointRule::class);

        return view('admin.rules.form', [
            'brand' => $this->currentBrand->require(),
            'rule' => new PointRule([
                'calc_type' => CalcType::Fixed,
                'event_type' => RuleEventType::Purchase,
                'active' => true,
            ]),
        ]);
    }

    public function store(StorePointRuleRequest $request): RedirectResponse
    {
        $this->authorize('create', PointRule::class);
        $brand = $this->currentBrand->require();

        PointRule::withoutGlobalScopes()->create($this->payload($request, (int) $brand->getKey()));

        return redirect()->route('admin.rules.index')->with('status', 'Regla creada.');
    }

    public function edit(PointRule $rule): View
    {
        $this->authorize('update', $rule);

        return view('admin.rules.form', [
            'brand' => $this->currentBrand->require(),
            'rule' => $rule,
        ]);
    }

    public function update(StorePointRuleRequest $request, PointRule $rule): RedirectResponse
    {
        $this->authorize('update', $rule);

        $rule->update($this->payload($request, (int) $rule->brand_id, false));

        return redirect()->route('admin.rules.index')->with('status', 'Regla actualizada.');
    }

    public function destroy(PointRule $rule): RedirectResponse
    {
        $this->authorize('delete', $rule);

        $rule->delete();

        return redirect()->route('admin.rules.index')->with('status', 'Regla eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(StorePointRuleRequest $request, int $brandId, bool $withBrand = true): array
    {
        $data = [
            'name' => (string) $request->string('name'),
            'event_type' => $request->input('event_type'),
            'calc_type' => $request->input('calc_type'),
            'value' => $request->input('value'),
            'min_amount' => $request->input('min_amount') ?: null,
            'expires_after_days' => $request->input('expires_after_days') ?: null,
            'starts_at' => $request->input('starts_at') ?: null,
            'ends_at' => $request->input('ends_at') ?: null,
            'active' => $request->boolean('active'),
        ];

        if ($withBrand) {
            $data['brand_id'] = $brandId;
        }

        return $data;
    }
}
