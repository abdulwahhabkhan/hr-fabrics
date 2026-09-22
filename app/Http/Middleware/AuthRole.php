<?php

namespace App\Http\Middleware;

use App\Contracts\PermissionChecker;
use Closure;
use Illuminate\Http\Request;

final readonly class AuthRole
{
    public function __construct(private PermissionChecker $permissionChecker) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $name = $request->route()->getName();
        $role_id = auth()->user()->role_id;

        if (! $this->permissionChecker->check($role_id, $name)) {
            abort(403, "You don't have permission to visit this page");
        }

        return $next($request);
    }
}
