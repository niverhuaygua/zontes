<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Brands\CurrentBrand;
use App\Domain\Customers\BrandMember;
use App\Domain\Customers\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly CurrentBrand $currentBrand) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $brandId = $this->currentBrand->id();

        $users = User::query()
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->when($brandId !== null, fn ($query) => $query->whereHas(
                'brandMembers',
                fn ($q) => $q->where('brand_id', $brandId),
            ))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
        ]);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.form', [
            'user' => $user->load('brandMembers.brand', 'administeredBrands'),
            'brands' => Brand::query()->orderBy('name')->get(),
            'roles' => UserRole::cases(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->update([
            'name' => (string) $request->string('name'),
            'role' => $request->input('role'),
        ]);

        if ($request->input('role') === UserRole::BrandAdmin->value) {
            $user->administeredBrands()->sync((array) $request->input('brands', []));
        } else {
            $user->administeredBrands()->detach();
        }

        return redirect()->route('admin.users.index')->with('status', 'Usuario actualizado.');
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $members = BrandMember::withoutGlobalScopes()
            ->where('user_id', $user->getKey())
            ->with(['brand', 'wallet'])
            ->get();

        return view('admin.users.show', [
            'user' => $user,
            'members' => $members,
        ]);
    }
}
