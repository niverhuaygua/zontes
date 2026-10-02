<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Customers\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta a uno o varios roles. Uso: ->middleware('role:super_admin,brand_admin')
 */
class EnsureUserRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $allowed = array_map(static fn (string $role): UserRole => UserRole::from($role), $roles);

        if (! in_array($user->role, $allowed, true)) {
            abort(403, 'No tienes permisos para acceder a esta seccion.');
        }

        return $next($request);
    }
}
