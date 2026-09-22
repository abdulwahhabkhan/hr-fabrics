<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordNotExpired
{
    /**
     * Route names an expired user may still reach in order to change the password or leave.
     *
     * @var array<int, string>
     */
    private const array ALLOWED_ROUTES = ['profile.index', 'profile.password', 'logout', 'password.confirm', 'password.confirmation'];

    /**
     * Send users with an expired password to the profile page.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isPasswordExpired() || $request->routeIs(self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        return redirect()->route('profile.index')
            ->with('error', __('Your password has expired. Please change it to continue.'));
    }
}
