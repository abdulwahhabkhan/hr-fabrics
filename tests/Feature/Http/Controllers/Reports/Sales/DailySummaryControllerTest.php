<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->fakeHavePermission();
});
it('loads the daily summary report without filters', function (): void {
    // Freeze time for deterministic expectations
    $this->travelTo(Carbon::create(2025, 12, 14, 10, 0, 0));

    $admin = $this->getAdmin();
    $this->actingAs($admin);

    $response = $this->get(route('reports.summary'));

    $response->assertOk();

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Reports/Summary/DailyReport')
        ->hasAll([
            'bank_details',
            'filters',
            'expenses',
            'expenses_total_expenses',
            'expenses_total_cr',
            'net_expenses',
            'sale_summary',
        ])
        ->where('filters.start_date', Carbon::now()->format('d-M-Y'))
        ->where('filters.end_date', Carbon::now()->format('d-M-Y'))
    );
});

it('loads the daily summary report with date filters', function (): void {
    $this->travelTo(Carbon::create(2025, 12, 14, 10, 0, 0));

    $admin = $this->getAdmin();
    $this->actingAs($admin);

    $query = [
        'start_date' => '2025-12-01',
        'end_date' => '2025-12-05',
    ];

    $response = $this->get(route('reports.summary', $query));

    $response->assertOk();

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Reports/Summary/DailyReport')
        ->hasAll([
            'bank_details',
            'filters',
            'expenses',
            'expenses_total_expenses',
            'expenses_total_cr',
            'net_expenses',
            'sale_summary',
        ])
        ->where('filters.start_date', Carbon::create(2025, 12, 1)->format('d-M-Y'))
        ->where('filters.end_date', Carbon::create(2025, 12, 5)->format('d-M-Y'))
    );
});
