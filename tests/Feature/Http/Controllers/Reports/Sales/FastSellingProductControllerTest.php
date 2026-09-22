<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->fakeHavePermission();
});
it('loads the fast selling products report without filters', function (): void {
    // Freeze time to avoid flaky date expectations
    $this->travelTo(Carbon::create(2025, 1, 15, 10, 0, 0));

    $admin = $this->getAdmin();
    $this->actingAs($admin);

    $response = $this->get(route('reports.fast-selling.products'));

    $response->assertOk();

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Reports/Products/FastMovingProductReport')
        ->hasAll(['products', 'total_qty', 'total_meters', 'vendors', 'filters'])
        ->where('filters.start_date', Carbon::now()->startOfMonth()->format('Y-m-d'))
        ->where('filters.end_date', Carbon::now()->format('Y-m-d'))
    );
});

it('loads the fast selling products report with date filters', function (): void {
    $this->travelTo(Carbon::create(2025, 2, 10, 9, 30, 0));

    $admin = $this->getAdmin();
    $this->actingAs($admin);

    $query = [
        'start_date' => '2025-02-01',
        'end_date' => '2025-02-05',
    ];

    $response = $this->get(route('reports.fast-selling.products', $query));

    $response->assertOk();

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Reports/Products/FastMovingProductReport')
        ->hasAll(['products', 'total_qty', 'total_meters', 'vendors', 'filters'])
        ->where('filters.start_date', '2025-02-01')
        ->where('filters.end_date', '2025-02-05')
    );
});
