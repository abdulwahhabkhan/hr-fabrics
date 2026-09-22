<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;

trait HandlesIndexFilters
{
    /**
     * Handle remembering, retrieving, or clearing index filters in session.
     *
     * @param  array<int, string>|string  $keys
     * @return array<string, mixed>
     */
    protected function filterSession(Request $request, array|string $keys = [], ?string $sessionKey = null): array
    {
        $sessionKey ??= $this->sessionKey ?? $this->session_key ?? null;

        if ($request->has('remember') && $request->input('remember') === 'forget') {
            if ($sessionKey) {
                session()->forget($sessionKey);
            }

            return [];
        }

        $filterKeys = is_array($keys) ? $keys : [$keys];
        $queryString = $request->only($filterKeys);

        if (! empty($queryString)) {
            $filters = $queryString;
            if ($sessionKey) {
                session([$sessionKey => $filters]);
            }
        } else {
            $filters = $sessionKey ? session($sessionKey, []) : [];
        }

        if (! empty($filters)) {
            $request->mergeIfMissing($filters);
        }

        return $filters;
    }
}
