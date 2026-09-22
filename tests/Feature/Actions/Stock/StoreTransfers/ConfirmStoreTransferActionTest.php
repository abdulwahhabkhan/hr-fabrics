<?php

namespace Tests\Feature\Action\Stock\StoreTransfers;

use App\Actions\Stock\StoreTransfers\ConfirmStoreTransfer;
use App\Enums\StoreTransferStatus;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Stock\StoreTransfer;
use App\Models\Stock\StoreTransferItem;

test('it closes the transfer and posts the ledger', function () {
    $user = $this->getAdmin();
    $store = Account::factory()->storeType()->create();
    $transfer = StoreTransfer::factory()->storeType()->create(['account_id' => $store->id]);
    StoreTransferItem::factory()->create(['store_transfer_id' => $transfer->id]);
    $transfer->refresh();

    $result = (new ConfirmStoreTransfer)->handle($transfer, $user);

    expect($result->status)->toBe(StoreTransferStatus::Closed)
        ->and($result->confirmed_at)->not->toBeNull();

    $this->verifyJournal($transfer->id, $store->id, $transfer->total, 0, $transfer->journalDetail());
    $this->assertDatabaseCount(Journal::class, 1);
});
