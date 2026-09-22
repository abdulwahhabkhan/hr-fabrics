<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Tests\Feature\Action\Stock\StoreTransfers;

use App\Actions\Stock\StoreTransfers\PostStoreTransferLedger;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Stock\StoreTransfer;

beforeEach(function () {
    $this->user = $this->getAdmin();
});

test('it posts a debit to the store account when type is store', function () {
    $store = Account::factory()->storeType()->create();
    $transfer = StoreTransfer::factory()->storeType()->create([
        'account_id' => $store->id, 'total' => 500, 'net_total' => 500,
    ]);

    (new PostStoreTransferLedger)->handle($transfer, $this->user);

    $this->verifyJournal($transfer->id, $store->id, 500, 0, $transfer->journalDetail());
    $this->verifyJournalDetail($store->id, 0, 500);
});

test('it posts a credit to the store account when type is return', function () {
    $store = Account::factory()->storeType()->create();
    $transfer = StoreTransfer::factory()->returnType()->create([
        'account_id' => $store->id, 'total' => 300, 'net_total' => 300,
    ]);

    (new PostStoreTransferLedger)->handle($transfer, $this->user);

    $this->verifyJournal($transfer->id, $store->id, 0, 300, $transfer->journalDetail());
    $this->verifyJournalDetail($store->id, 300, 0);
});

test('it posts the net total, not the item subtotal, when expenses and discount apply', function () {
    $store = Account::factory()->storeType()->create();
    $transfer = StoreTransfer::factory()->storeType()->create([
        'account_id' => $store->id,
        'total' => 500,
        'expenses' => 80,
        'discount_on_total' => 30,
        'net_total' => 550,
    ]);

    (new PostStoreTransferLedger)->handle($transfer, $this->user);

    $this->verifyJournal($transfer->id, $store->id, 550, 0, $transfer->journalDetail());
    $this->verifyJournalDetail($store->id, 0, 550);
});

test('it is idempotent and does not duplicate the journal on re-run', function () {
    $store = Account::factory()->storeType()->create();
    $transfer = StoreTransfer::factory()->storeType()->create([
        'account_id' => $store->id, 'total' => 500, 'net_total' => 500,
    ]);

    (new PostStoreTransferLedger)->handle($transfer, $this->user);
    (new PostStoreTransferLedger)->handle($transfer, $this->user);

    $this->assertDatabaseCount(Journal::class, 1);
});
