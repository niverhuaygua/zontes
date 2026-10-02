<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\BrandSwitchController;
use App\Http\Controllers\Admin\CsvImportController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LedgerController;
use App\Http\Controllers\Admin\PointRuleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\Portal\PortalEntryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Raiz y autenticacion
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/entrar', [LoginController::class, 'show'])->name('login');
    Route::post('/entrar', [LoginController::class, 'store'])->name('login.store');
});

Route::post('/salir', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->get('/portal', PortalEntryController::class)->name('portal.entry');

/*
|--------------------------------------------------------------------------
| Panel administrativo (sin prefijo de marca; la marca se resuelve por sesion)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super_admin,brand_admin', 'admin.brand'])->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/marca-activa', BrandSwitchController::class)->name('brand.switch');

    Route::middleware('role:super_admin')->group(function (): void {
        Route::get('marcas', [BrandController::class, 'index'])->name('brands.index');
        Route::get('marcas/crear', [BrandController::class, 'create'])->name('brands.create');
        Route::post('marcas', [BrandController::class, 'store'])->name('brands.store');
        Route::get('marcas/{brand}/editar', [BrandController::class, 'edit'])->name('brands.edit');
        Route::put('marcas/{brand}', [BrandController::class, 'update'])->name('brands.update');
    });

    Route::get('usuarios', [UserController::class, 'index'])->name('users.index');
    Route::get('usuarios/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('usuarios/{user}/editar', [UserController::class, 'edit'])->name('users.edit');
    Route::put('usuarios/{user}', [UserController::class, 'update'])->name('users.update');

    Route::resource('reglas', PointRuleController::class)
        ->parameters(['reglas' => 'rule'])
        ->names('rules')
        ->except(['show']);

    Route::get('importaciones', [CsvImportController::class, 'index'])->name('imports.index');
    Route::get('importaciones/crear', [CsvImportController::class, 'create'])->name('imports.create');
    Route::post('importaciones', [CsvImportController::class, 'store'])->name('imports.store');
    Route::get('importaciones/{import}', [CsvImportController::class, 'show'])->name('imports.show');
    Route::post('importaciones/{import}/confirmar', [CsvImportController::class, 'confirm'])->name('imports.confirm');
    Route::get('importaciones/{import}/errores', [CsvImportController::class, 'downloadErrors'])->name('imports.errors');

    Route::get('movimientos', [LedgerController::class, 'index'])->name('ledger.index');
    Route::post('movimientos/ajuste', [LedgerController::class, 'adjust'])->name('ledger.adjust');
});

/*
|--------------------------------------------------------------------------
| Portal cliente: cada marca se resuelve por prefijo de ruta (/{brand})
| En produccion tambien por subdominio (zontes.dominio.bo)
|--------------------------------------------------------------------------
*/

Route::prefix('{brand}')
    ->where(['brand' => '[a-z0-9\-]+'])
    ->group(function (): void {
        Route::get('/', [PortalController::class, 'index'])->name('portal.home');

        Route::middleware('guest')->group(function (): void {
            Route::get('/registro', [RegisterController::class, 'show'])->name('register');
            Route::post('/registro', [RegisterController::class, 'store'])->name('register.store');
        });
    });
