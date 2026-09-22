<?php

use App\Enums\StatusText;
use App\Models\Accounts\Journal;
use App\Models\Purchase\Purchase;
use Inertia\Testing\AssertableInertia as Assert;

test('receipt ledger detail page can be rendered', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.pos.ledger');
    $receipt = Purchase::factory()->create(['status' => StatusText::Close]);
    Journal::factory()
        ->hasTransactions(2)
        ->create([
            'resource_type' => $receipt->getMorphClass(),
            'resource_id' => $receipt->id,
        ]);

    // Act
    $response = $this->actingAs($user)->get(route('purchases.pos.ledger', $receipt));

    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Accounts/JournalSummary')
        ->has('journal')
        ->has('back_url')
        ->has('page_header')
    );
});

test('receipt ledger detail is forbidden for an open receipt', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.pos.ledger');
    $receipt = Purchase::factory()->create(['status' => StatusText::Open]);

    // Act
    $response = $this->actingAs($user)->get(route('purchases.pos.ledger', $receipt));

    // Assert
    $response->assertForbidden();
});
