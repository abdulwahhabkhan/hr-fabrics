<?php

use Inertia\Testing\AssertableInertia as Assert;

it('loads the product history page with expected props', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'stocks.product-history');

    // Act
    $response = $this->actingAs($user)->get(route('stocks.product-history'));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Stock/Inventory/ProductHistory')
        ->has('items')
        ->has('products')
        ->has('product')
        ->has('filters')
        ->where('total_meters', 0)
    );
});
