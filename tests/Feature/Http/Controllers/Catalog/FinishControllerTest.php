<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Models\Catalog\Finish;

beforeEach(function (): void {
    $this->fakeHavePermission();
});
test('finish list', function () {
    // Arrange
    $user = $this->getAdmin();
    Finish::factory()->count(10)->create();

    // Act
    $response = $this->actingAs($user)->get(route('catalog.finish.index'));

    // Assert
    $response->assertOk();
});

test('create finish', function () {
    // Arrange
    $user = $this->getAdmin();
    $body = ['name' => 'finish', 'description' => 'finish desc'];

    // Act
    $response = $this->actingAs($user)->post(route('catalog.finish.store'), $body);

    // Assert
    $response->assertSessionHasNoErrors()->assertCreated();
    $this->assertDatabaseHas(Finish::class, $body);
});

test('update finish', function () {
    // Arrange
    $user = $this->getAdmin();
    $body = ['name' => 'finish updated', 'description' => 'finish desc'];
    $finish = Finish::factory()->create();

    // Act
    $response = $this->actingAs($user)->put(route('catalog.finish.update', $finish->id), $body);

    // Assert
    $response->assertSessionHasNoErrors()->assertAccepted();
    $this->assertDatabaseHas(Finish::class, $body);
});
