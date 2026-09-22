<?php

namespace App\Actions\Inbound\Purchase;

use App\Actions\LogAction\RecordAction;
use App\Models\Purchase\Purchase;
use App\Models\User;
use Throwable;

class ConfirmPurchaseActions
{
    /**
     * @throws Throwable
     */
    public function handle(Purchase $receipt, User $user): void
    {
        $receipt->stocks()->update(['invoiced' => true]);
        resolve(ProcessPurchaseLedger::class)->handle($receipt, $user);
        resolve(UpdateStockPrice::class)->handle($receipt);
        resolve(RecordAction::class)->handle($receipt, $user, 'Receipt Confirmed');
    }
}
