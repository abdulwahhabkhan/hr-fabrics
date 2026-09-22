<?php

use App\Services\Features;

it('reports a feature as enabled when it is listed in the fortify features config', function () {
    config()->set('fortify.features', ['registration']);

    expect(Features::enabled('registration'))->toBeTrue();
});

it('reports a feature as disabled when it is not listed in the fortify features config', function () {
    config()->set('fortify.features', ['registration']);

    expect(Features::enabled('two-factor-authentication'))->toBeFalse();
});

it('reports a feature as disabled when the fortify features config is empty', function () {
    config()->set('fortify.features', []);

    expect(Features::enabled('registration'))->toBeFalse();
});
