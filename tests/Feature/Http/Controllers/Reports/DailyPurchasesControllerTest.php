<?php

beforeEach(function (): void {
    $this->fakeHavePermission();
});
test('page loaded', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->actingAs($user);
    // Action
    $response = $this->get(route('reports.purchases_daily'));
    // Assert
    $response->assertOk();
});
