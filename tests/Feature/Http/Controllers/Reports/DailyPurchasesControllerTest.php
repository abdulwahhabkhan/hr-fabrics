<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->fakeHavePermission();
});
test('page loaded', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->actingAs($user);
    // Action
    $response = $this->get(route('reports.purchases_daily'));
    // Assert
    $response->assertOk();
});

test('filters are returned as date strings', function () {
    // Arrange
    $user = $this->getAdmin();

    // Act
    $defaultResponse = $this->actingAs($user)->get(route('reports.purchases_daily'));
    $filteredResponse = $this->actingAs($user)->get(route('reports.purchases_daily', [
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-24',
    ]));

    // Assert
    $defaultResponse->assertInertia(fn (Assert $page) => $page
        ->where('filters.start_date', today()->toDateString())
        ->where('filters.end_date', today()->toDateString())
    );
    $filteredResponse->assertInertia(fn (Assert $page) => $page
        ->where('filters.start_date', '2026-09-01')
        ->where('filters.end_date', '2026-09-24')
    );
});
