<?php

use App\Actions\Inbound\Purchase\ConfirmPurchaseActions;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use App\Models\Purchase\FabricReceiving;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;


test('unlock receipt', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'actions.purchase.open');

    $stock = FabricReceiving::factory()->create(['invoiced' => true]);
    $receipts = Purchase::factory(2)->confirmed()
        ->has(
            PurchaseItem::factory()->thaan()->count(3),
            'items'
        )->create(['stock_id' => $stock->id, 'stock_ids' => $stock->id]);
    $body = [];
    $receipt = $receipts->first();
    $receipt_2 = $receipts[1];

    resolve(ConfirmPurchaseActions::class)->handle($receipt, $user);
    resolve(ConfirmPurchaseActions::class)->handle($receipt_2, $user);

    // Act
    $response = $this
        ->actingAs($user)
        ->post(route('actions.purchase.open', $receipt->id), $body);

    // Assert
    $response
        ->assertRedirect(route('purchases.pos.edit', $receipt->id));
    assertDatabaseHas(FabricReceiving::class, [
        'id' => $stock->id,
        'invoiced' => false,
    ]);
    assertDatabaseMissing(Journal::class, [
        'resource_type' => Purchase::morphClass(),
        'resource_id' => $receipt->id,
    ]);

    assertDatabaseMissing(JournalDetail::class, [
        'account_id' => $receipt->supplier_id,
    ]);

    assertDatabaseHas(Journal::class, [
        'resource_type' => Purchase::morphClass(),
        'resource_id' => $receipt_2->id,
    ]);

    assertDatabaseHas(JournalDetail::class, [
        'account_id' => $receipt_2->supplier_id,
    ]);
});