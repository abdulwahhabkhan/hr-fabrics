<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function (): void {
    $user = $this->getAdmin();
    $this->actingAs($user);
    $this->fakeHavePermission();
});

test('brand valuation page loads with required keys', function (): void {
    // Action
    $response = $this->get(route('stocks.value-by-brand', ['type' => '']));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stock/Inventory/InventoryValuationByBrand')
        ->has('items')
        ->has('total_value')
        ->has('brands')
        ->has('filters')
    );
});
