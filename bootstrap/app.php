<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\ResolveAdminBrand;
use App\Http\Middleware\ResolveBrand;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            ResolveBrand::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserRole::class,
            'admin.brand' => ResolveAdminBrand::class,
            'guest.only' => RedirectIfAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return response()->view('errors.403', [], 403);
        });
    })->create();
