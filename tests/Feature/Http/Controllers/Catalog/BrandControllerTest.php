<?php

use App\Models\Catalog\Brand;

beforeEach(function (): void {
    $this->fakeHavePermission();
});

test('brands list', function () {
    // Arrange
    $user = $this->getAdmin();
    Brand::factory()->count(10)->create();

    // Act
    $response = $this->actingAs($user)->get(route('catalog.brands.index'));

    // Assert
    $response->assertOk();
});

test('create brand', function () {
    // Arrange
    $user = $this->getAdmin();
    $body = ['name' => 'brand', 'description' => 'brand desc'];

    // Act
    $response = $this->actingAs($user)->post(route('catalog.brands.store'), $body);

    // Assert
    $response->assertSessionHasNoErrors()->assertCreated();
    $this->assertDatabaseHas(Brand::class, $body);
});

test('update brand', function () {
    // Arrange
    $user = $this->getAdmin();
    $body = ['name' => 'brand updated', 'description' => 'brand desc'];
    $brand = Brand::factory()->create();

    // Act
    $response = $this->actingAs($user)->put(route('catalog.brands.update', $brand->id), $body);

    // Assert
    $response->assertSessionHasNoErrors()->assertAccepted();
    $this->assertDatabaseHas(Brand::class, $body);
});
