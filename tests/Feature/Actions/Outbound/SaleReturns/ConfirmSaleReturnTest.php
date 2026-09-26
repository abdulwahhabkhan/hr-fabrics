<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Outbound\SaleReturns\ConfirmSaleReturn;
use App\Enums\Module;
use App\Enums\PaymentMode;
use App\Enums\TransactionType;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Models\Action\Log;
use App\Models\Sales\SalesReturn;
use App\Models\Sales\SalesReturnItem;
use App\Models\Stock\Inventory;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->customer = Account::factory()->customer()->create();
});

test('confirming an open return does nothing', function () {
    $return = SalesReturn::factory()
        ->has(SalesReturnItem::factory(), 'returnItems')
        ->create(['customer_id' => $this->customer->id]);

    resolve(ConfirmSaleReturn::class)->handle($return, $this->user);

    assertDatabaseCount(Journal::class, 0);
    assertDatabaseCount(Inventory::class, 0);
    assertDatabaseCount(Log::class, 0);
});

test('confirming a closed return posts ledger, records balance and adds inventory', function () {
    JournalDetail::factory()->debit(700)->create(['account_id' => $this->customer->id]);
    $return = SalesReturn::factory()
        ->closed()
        ->has(SalesReturnItem::factory()->state(['qty' => 2.6, 'size' => 10, 'total_qty' => 26]), 'returnItems')
        ->create([
            'customer_id' => $this->customer->id,
            'payment_mode' => PaymentMode::Credit->value,
            'total_amount' => 1000,
        ]);
    $item = $return->returnItems->first();

    resolve(ConfirmSaleReturn::class)->handle($return, $this->user);

    expect($return->refresh()->balance)->toEqual(700);
    $this->verifyJournal($return->id, $this->customer->id, 0, 1000, $return->journalDetail());
    assertDatabaseHas(Log::class, [
        'loggable_type' => $return->getMorphClass(),
        'loggable_id' => $return->id,
        'log' => json_encode([
            'action' => 'Sale Return confirmed by user', 'user' => ['id' => $this->user->id, 'name' => $this->user->name],
        ]),
    ]);

    assertDatabaseCount(Inventory::class, 1);
    $inventory = $return->inventories()->sole();
    expect($inventory)
        ->product_id->toBe($item->product_id)
        ->stockable_item_id->toBe($item->id)
        ->unit->toBe($item->unit)
        ->size->toEqual(10)
        ->meters->toEqual(26)
        ->qty->toBe(3)
        ->transaction_date->toDateString()->toBe($return->transaction_date->toDateString())
        ->and($inventory->info->all())->toBe([
            'inbound' => [
                'action' => 'add',
                'invoice_no' => $return->invoice_no,
                'type' => TransactionType::SaleOrderReturn->value,
                'module' => Module::SaleOrderReturn->value,
            ],
        ]);
});

test('re-confirming a return replaces its inventory instead of duplicating it', function () {
    $return = SalesReturn::factory()
        ->closed()
        ->has(SalesReturnItem::factory()->count(2), 'returnItems')
        ->create([
            'customer_id' => $this->customer->id,
            'payment_mode' => PaymentMode::Credit->value,
        ]);

    resolve(ConfirmSaleReturn::class)->handle($return, $this->user);
    resolve(ConfirmSaleReturn::class)->handle($return, $this->user);

    assertDatabaseCount(Inventory::class, 2);
});
