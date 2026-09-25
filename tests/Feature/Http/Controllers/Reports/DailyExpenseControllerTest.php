<?php

use Inertia\Testing\AssertableInertia as Assert;

it('loads the daily expenses report page with expected keys', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'reports.account-report.expenses');

    // Act
    $response = $this->actingAs($user)->get(route('reports.account-report.expenses'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Reports/Daily/DailyExpensesReport')
        ->where('filters.start_date', today()->toDateString())
        ->where('filters.end_date', today()->toDateString())
        ->has('rows')
        ->has('total_expenses')
        ->has('total_returns')
        ->has('net_expenses')
    );
});
