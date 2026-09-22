<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class QueryKeyAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->query('key');
        $request->headers->set('Accept', 'application/json');
        $validKey = config('store.auth_key');
        if (! $key || $key !== $validKey) {
            return response()->json([
                'message' => 'Unauthorized. Invalid or missing API key.',
            ], 401);
        }

        return $next($request);
    }
}
