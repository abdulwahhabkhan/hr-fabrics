<?php

use App\Enums\ReturnStatus;
use App\Models\Accounts\Journal;
use App\Models\Sales\SalesReturn;
use Inertia\Testing\AssertableInertia as Assert;

test('sales return ledger detail page can be rendered', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'sales.returns.ledger');
    $return = SalesReturn::factory()->closed()->create();
    Journal::factory()
        ->hasTransactions(2)
        ->create([
            'resource_type' => $return->getMorphClass(),
            'resource_id' => $return->id,
        ]);

    // Act
    $response = $this->actingAs($user)->get(route('sales.returns.ledger', $return));

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

test('sales return ledger detail is forbidden for an open return', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'sales.returns.ledger');
    $return = SalesReturn::factory()->create(['status' => ReturnStatus::Open]);

    // Act
    $response = $this->actingAs($user)->get(route('sales.returns.ledger', $return));

    // Assert
    $response->assertForbidden();
});
