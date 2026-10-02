<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Gestion de marcas: solo el super_admin. Agregar una marca no requiere codigo.
 */
class BrandController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Brand::class);

        return view('admin.brands.index', [
            'brands' => Brand::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Brand::class);

        return view('admin.brands.form', ['brand' => new Brand]);
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $brand = Brand::query()->create([
            'slug' => Str::slug((string) $request->string('slug')),
            'name' => (string) $request->string('name'),
            'short_name' => (string) $request->string('short_name'),
            'currency' => 'BOB',
            'active' => $request->boolean('active', true),
            'theme' => [
                '--brand-primary' => (string) $request->string('primary'),
                '--brand-primary-dark' => (string) $request->string('primary_dark'),
                '--brand-accent' => (string) $request->string('accent'),
            ],
        ]);

        return redirect()->route('admin.brands.index')->with('status', "Marca {$brand->name} creada.");
    }

    public function edit(Brand $brand): View
    {
        $this->authorize('update', $brand);

        return view('admin.brands.form', ['brand' => $brand]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update([
            'name' => (string) $request->string('name'),
            'short_name' => (string) $request->string('short_name'),
            'active' => $request->boolean('active'),
            'theme' => [
                '--brand-primary' => (string) $request->string('primary'),
                '--brand-primary-dark' => (string) $request->string('primary_dark'),
                '--brand-accent' => (string) $request->string('accent'),
            ],
        ]);

        return redirect()->route('admin.brands.index')->with('status', "Marca {$brand->name} actualizada.");
    }
}
