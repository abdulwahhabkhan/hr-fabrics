<?php

use App\Http\Controllers\Traits\HandlesIndexFilters;
use Illuminate\Http\Request;

test('it saves query string filters to session using property sessionKey and returns them', function () {
    $controller = new class
    {
        use HandlesIndexFilters;

        protected string $sessionKey = 'test.filters';

        public function test(Request $request, array|string $keys): array
        {
            return $this->filterSession($request, $keys);
        }
    };

    $request = Request::create('/test?status=active&search=shoes', 'GET');
    $request->setLaravelSession(app('session.store'));

    $filters = $controller->test($request, ['status', 'search', 'type']);

    expect($filters)->toBe([
        'status' => 'active',
        'search' => 'shoes',
    ])
        ->and(session('test.filters'))->toBe([
            'status' => 'active',
            'search' => 'shoes',
        ])
        ->and($request->input('status'))->toBe('active')
        ->and($request->input('search'))->toBe('shoes');
});

test('it retrieves cached filters from session when request has no query filters and merges them into request',
    function () {
        $controller = new class
        {
            use HandlesIndexFilters;

            protected string $sessionKey = 'test.filters';

            public function test(Request $request, array|string $keys): array
            {
                return $this->filterSession($request, $keys);
            }
        };

        session(['test.filters' => ['status' => 'pending', 'page_size' => 25]]);

        $request = Request::create('/test', 'GET');
        $request->setLaravelSession(app('session.store'));

        $filters = $controller->test($request, ['status', 'page_size']);

        expect($filters)->toBe([
            'status' => 'pending',
            'page_size' => 25,
        ])
            ->and($request->input('status'))->toBe('pending')
            ->and($request->input('page_size'))->toBe(25);
    });

test('it clears session and returns empty array when remember is set to forget', function () {
    $controller = new class
    {
        use HandlesIndexFilters;

        protected string $sessionKey = 'test.filters';

        public function test(Request $request, array|string $keys): array
        {
            return $this->filterSession($request, $keys);
        }
    };

    session(['test.filters' => ['status' => 'active']]);

    $request = Request::create('/test?remember=forget', 'GET');
    $request->setLaravelSession(app('session.store'));

    $filters = $controller->test($request, ['status']);

    expect($filters)->toBeEmpty()
        ->and(session()->has('test.filters'))->toBeFalse();
});

test('it works without session key without throwing exceptions', function () {
    $controller = new class
    {
        use HandlesIndexFilters;

        public function test(Request $request, array|string $keys): array
        {
            return $this->filterSession($request, $keys);
        }
    };

    $request = Request::create('/test?query=term', 'GET');
    $request->setLaravelSession(app('session.store'));

    $filters = $controller->test($request, ['query']);

    expect($filters)->toBe(['query' => 'term']);
});

test('it accepts a single string key instead of array', function () {
    $controller = new class
    {
        use HandlesIndexFilters;

        protected string $sessionKey = 'single.key';

        public function test(Request $request, array|string $keys): array
        {
            return $this->filterSession($request, $keys);
        }
    };

    $request = Request::create('/test?single=val', 'GET');
    $request->setLaravelSession(app('session.store'));

    $filters = $controller->test($request, 'single');

    expect($filters)->toBe(['single' => 'val'])
        ->and(session('single.key'))->toBe(['single' => 'val']);
});
