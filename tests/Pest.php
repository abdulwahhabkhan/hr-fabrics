<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithCachedRoutes;
use Tests\TestCase;

use function Pest\Laravel\freezeSecond;
use function Pest\Laravel\withoutVite;

pest()->extend(TestCase::class)
    // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->use(LazilyRefreshDatabase::class)
    ->use(WithCachedRoutes::class)
    // ->use(WithCachedPermissions::class)
    ->beforeEach(function () {
        Http::preventStrayRequests();
        freezeSecond();
        withoutVite();
    })
    ->in('Feature');
