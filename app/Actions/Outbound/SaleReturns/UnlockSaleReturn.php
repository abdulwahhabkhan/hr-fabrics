<?php

namespace App\Actions\Outbound\SaleReturns;

use App\Actions\LogAction\RecordAction;
use App\Enums\ReturnStatus;
use App\Models\Accounts\Journal;
use App\Models\Sales\SalesReturn;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;

class UnlockSaleReturn
{
    /**
     * @throws Exception
     */
    public function handle(SalesReturn $return, User $user): void
    {
        resolve(RecordAction::class)->handle($return, $user, 'SOR Unlocked');

        /** @var Collection<int, Journal> $journals */
        $journals = $return->journal()->get();
        foreach ($journals as $journal) {
            $journal->transactions()->delete();
        }
        $return->journal()->delete();
        $return->inventories()->delete();
        $return->update(['status' => ReturnStatus::Open]);

    }
}
