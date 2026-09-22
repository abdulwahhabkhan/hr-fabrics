<?php

namespace App\Actions\Inbound\Return;

use App\Actions\LogAction\RecordAction;
use App\Models\Accounts\Journal;
use App\Models\Purchase\PurchaseReturn;
use App\Models\User;
use Auth;
use Exception;
use Illuminate\Support\Collection;

class UnlockReturn
{
    /**
     * @throws Exception
     */
    public function handle(PurchaseReturn $return, User $user): void
    {
        resolve(RecordAction::class)
            ->handle(
                $return,
                $user,
                'POR Unlocked',
                [
                    'ref_no' => $return->invoice_no,
                    'created_by' => Auth::user(),
                    'info' => 'POR unlocked by '.auth()->user()->name,
                ]);
        $this->dissociateInventory($return);
        $this->removeLedger($return);

    }

    protected function dissociateInventory(PurchaseReturn $return): void
    {
        $return->inventories()->update([
            'transaction_date' => null,
            'outbound_type' => null,
            'outbound_id' => null,
            'outbound_item_id' => null,
        ]);
    }

    protected function removeLedger(PurchaseReturn $return): void
    {
        /** @var Collection<int, Journal> $journals */
        $journals = $return->journal()
            ->get();
        foreach ($journals as $journal) {
            $journal->transactions()->delete();
        }
        $return->journal()->delete();
    }
}
