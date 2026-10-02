<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Conmutador de marca del panel admin (solo para marcas que el usuario administra).
 */
class BrandSwitchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $brand = Brand::query()->findOrFail($request->integer('brand_id'));
        $user = $request->user();

        abort_unless($user !== null && ($user->isSuperAdmin() || $user->administersBrand($brand)), 403);

        $request->session()->put('admin_brand_id', $brand->getKey());

        return redirect()->route('admin.dashboard')->with('status', "Marca activa: {$brand->name}.");
    }
}
