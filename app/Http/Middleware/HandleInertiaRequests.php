<?php

namespace App\Http\Middleware;

use App\Enums\AccountType;
use App\Enums\PackingType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {

        return array_merge(parent::share($request), [
            'appName' => config('app.name'),
            'store' => config('store'),
            'packingUnits' => Inertia::once(fn () => PackingType::toValues()),
            'accountTypes' => Inertia::once(fn () => AccountType::accountType()),
            'auth' => [
                'user' => $request->user() ?
                    $request->user()->only(['id', 'name', 'email']) : null,
                'permissions' => $request->user() ? Inertia::once(fn (
                ) => $request->user()->permissions()->toArray()) : null,
            ],
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ]);
    }
}
