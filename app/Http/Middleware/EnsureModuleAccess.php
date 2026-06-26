<?php

namespace App\Http\Middleware;

use App\Services\RolePermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    public function __construct(
        private readonly RolePermissionService $permissions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (in_array($routeName, ['logout'], true)) {
            return $next($request);
        }

        $module = $this->permissions->moduleForRoute($routeName);

        if ($module === null) {
            return $next($request);
        }

        if (! $user->canAccessModule($module)) {
            abort(403, 'Vous n\'avez pas accès à ce module.');
        }

        return $next($request);
    }
}
