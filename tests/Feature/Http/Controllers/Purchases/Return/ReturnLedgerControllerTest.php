<?php

use App\Enums\ReturnStatus;
use App\Models\Accounts\Journal;
use App\Models\Purchase\PurchaseReturn;
use Inertia\Testing\AssertableInertia as Assert;

test('po return ledger detail page can be rendered', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.por.ledger');
    $return = PurchaseReturn::factory()->closed()->create();
    Journal::factory()
        ->hasTransactions(2)
        ->create([
            'resource_type' => $return->getMorphClass(),
            'resource_id' => $return->id,
        ]);

    // Act
    $response = $this->actingAs($user)->get(route('purchases.por.ledger', $return));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounts/JournalSummary')
        ->has('journal')
        ->has('back_url')
        ->has('page_header')
        ->where('reference_no', $return->invoice_no)
    );
});

test('po return ledger detail is forbidden for an open return', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.por.ledger');
    $return = PurchaseReturn::factory()->create(['status' => ReturnStatus::Open]);

    // Act
    $response = $this->actingAs($user)->get(route('purchases.por.ledger', $return));

    // Assert
    $response->assertForbidden();
});
