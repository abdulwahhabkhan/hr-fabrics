<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $this->fakeHavePermission();
});
/**
 * Basic feature test for the Cash/Credit Sales report endpoint.
 */
it('renders the CashCredit report page with expected props', function (): void {
    // If authentication is required, use helper to get an admin user; otherwise this is harmless.
    if (method_exists($this, 'getAdmin')) {
        $this->actingAs($this->getAdmin());
    }

    $response = $this->get(route('reports.sale-cash-credit'));

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/Daily/CashCreditReport')
        ->hasAll(['filters', 'sale_summary', 'total_cash', 'total_credit'])
        ->where('filters.start_date', today()->toDateString())
        ->where('filters.end_date', today()->toDateString())
    );
});
