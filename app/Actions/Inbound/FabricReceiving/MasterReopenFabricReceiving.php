<?php

namespace App\Actions\Inbound\FabricReceiving;

use App\Actions\Inbound\Purchase\UnLockPurchase;
use App\Actions\LogAction\RecordAction;
use App\Enums\StatusText;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\Purchase;
use App\Models\User;
use DB;
use Throwable;

final class MasterReopenFabricReceiving
{
    /**
     * Reopen a confirmed receiving together with the purchase vouchers it is invoiced on, so quantities and prices
     * can be corrected. Stock still on hand is removed; stock booked to sale orders stays booked until the receiving
     * is confirmed again, then those orders are reallocated. Transaction dates are kept.
     *
     * @throws Throwable
     */
    public function handle(FabricReceiving $stock, User $user): void
    {
        DB::transaction(function () use ($stock, $user): void {
            resolve(RecordAction::class)->handle($stock, $user, 'Stock reopened by master user');

            $unLockPurchase = resolve(UnLockPurchase::class);
            $stock->purchases()->confirmed()->get()
                ->each(fn (Purchase $purchase) => $unLockPurchase->handle($purchase, $user));

            $stock->inventories()->whereNull('outbound_id')->delete();
            $stock->update(['status' => StatusText::Open]);
        });
    }
}
