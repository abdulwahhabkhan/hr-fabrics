<?php

use App\Models\Accounts\Journal;
use App\Models\Stock\StoreTransfer;
use Inertia\Testing\AssertableInertia as Assert;

test('store transfer ledger detail page can be rendered', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'stocks.store-transfers.ledger');
    $transfer = StoreTransfer::factory()->closed()->create();
    Journal::factory()
        ->hasTransactions(2)
        ->create([
            'resource_type' => $transfer->getMorphClass(),
            'resource_id' => $transfer->id,
        ]);

    // Act
    $response = $this->actingAs($user)->get(route('stocks.store-transfers.ledger', $transfer));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounts/JournalSummary')
        ->has('journal')
        ->where('reference_no', $transfer->transfer_no)
        ->has('back_url')
        ->has('page_header')
    );
});

test('store transfer ledger detail is forbidden for an open transfer', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'stocks.store-transfers.ledger');
    $transfer = StoreTransfer::factory()->create();

    // Act
    $response = $this->actingAs($user)->get(route('stocks.store-transfers.ledger', $transfer));

    // Assert
    $response->assertForbidden();
});

test('store transfer ledger detail is forbidden without the ledger permission', function () {
    // Arrange
    $user = $this->userWithoutPermissions();
    $transfer = StoreTransfer::factory()->closed()->create();

    // Act
    $response = $this->actingAs($user)->get(route('stocks.store-transfers.ledger', $transfer));

    // Assert
    $response->assertForbidden();
});
