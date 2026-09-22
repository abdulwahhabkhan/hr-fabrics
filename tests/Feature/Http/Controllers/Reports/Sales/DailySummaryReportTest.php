<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->fakeHavePermission();
});
it('shows the Daily Summary Report page', function (): void {
    $user = $this->getAdmin();
    $this->actingAs($user);

    $response = $this->get(route('reports.sales-daily'));

    $response->assertSuccessful();

    $response->assertInertia(function (Assert $page): void {
        $page->component('Reports/Daily/DailySummaryReport')
            ->has('filters')
            ->has('rows')
            ->has('total_sales')
            ->has('total_receipts')
            ->has('total_returns');
    });
});
