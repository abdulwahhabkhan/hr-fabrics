<?php

use Inertia\Testing\AssertableInertia;

beforeEach(function (): void {
    $this->fakeHavePermission();
});
it('shows daily average report without filters', function (): void {
    $this->actingAs($this->getAdmin());

    $response = $this->get(route('reports.sales-daily-average'));

    $response->assertSuccessful()
        ->assertInertia(function (AssertableInertia $page): void {
            $page->component('Reports/Sales/DailyAverage')
                ->has('filters')
                ->has('sale_summary')
                ->has('returns')
                ->has('sales')
                ->has('total_amount')
                ->has('total_meters')
                ->has('holidays')
                ->has('fridays')
                ->has('total_holidays')
                ->has('working_days')
                ->has('average_per_day');
        });
});

it('returns daily average report with date filters', function (): void {
    $this->actingAs($this->getAdmin());

    $params = [
        'start_date' => now()->subDays(7)->toDateString(),
        'end_date' => now()->toDateString(),
    ];

    $response = $this->get(route('reports.sales-daily-average', $params));

    $response->assertSuccessful()
        ->assertInertia(function (AssertableInertia $page): void {
            $page->component('Reports/Sales/DailyAverage')
                ->has('filters')
                ->has('returns')
                ->has('sales')
                ->has('total_amount')
                ->has('total_meters')
                ->has('holidays')
                ->has('fridays')
                ->has('total_holidays')
                ->has('working_days')
                ->has('average_per_day');
        });
});
