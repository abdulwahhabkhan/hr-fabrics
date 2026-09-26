<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Outbound\SaleReturns\ConfirmSaleReturn;
use App\Actions\Outbound\SaleReturns\UnlockSaleReturn;
use App\Enums\PaymentMode;
use App\Enums\ReturnStatus;
use App\Models\Accounts\Journal;
use App\Models\Accounts\JournalDetail;
use App\Models\Action\Log;
use App\Models\Sales\SalesReturn;
use App\Models\Sales\SalesReturnItem;
use App\Models\Stock\Inventory;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

test('unlock reopens a closed return, wiping its journal and inventory', function () {
    $user = $this->getAdmin();
    $return = SalesReturn::factory()
        ->closed()
        ->has(SalesReturnItem::factory()->count(2), 'returnItems')
        ->create(['payment_mode' => PaymentMode::Credit->value, 'total_amount' => 1000]);
    resolve(ConfirmSaleReturn::class)->handle($return, $user);

    assertDatabaseCount(Journal::class, 1);
    assertDatabaseCount(Inventory::class, 2);

    resolve(UnlockSaleReturn::class)->handle($return, $user);

    expect($return->refresh()->status)->toBe(ReturnStatus::Open);
    assertDatabaseCount(Journal::class, 0);
    assertDatabaseCount(JournalDetail::class, 0);
    assertDatabaseCount(Inventory::class, 0);
    assertDatabaseHas(Log::class, [
        'loggable_type' => $return->getMorphClass(),
        'loggable_id' => $return->id,
        'log' => json_encode([
            'action' => 'SOR Unlocked', 'user' => ['id' => $user->id, 'name' => $user->name],
        ]),
    ]);
});
