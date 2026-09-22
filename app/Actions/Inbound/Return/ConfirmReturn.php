<?php

namespace App\Actions\Inbound\Return;

use App\Actions\Accounts\LedgerEntry;
use App\Actions\LogAction\RecordAction;
use App\Enums\JournalHead;
use App\Models\Purchase\PurchaseReturn;
use App\Models\User;
use Exception;
use Throwable;

class ConfirmReturn
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function handle(PurchaseReturn $return, User $user): void
    {
        resolve(RecordAction::class)->handle($return, $user, 'Confirmed');
        $this->processLedger($return, $user);
        $this->processInventory($return);
    }

    public function returnItem(PurchaseReturn $return, $item) {}

    /**
     * @throws Exception
     */
    private function processLedger(PurchaseReturn $return, User $user): void
    {
        resolve(LedgerEntry::class)
            ->setTransactionDate($return->transaction_date)
            ->setUser($user)
            ->setHead(JournalHead::Purchases)
            ->init($return)
            ->credit($return->supplier_id, $return->total)
            ->logAction();
    }

    /**
     * @throws Throwable
     */
    private function processInventory(PurchaseReturn $return): void
    {
        $returnStock = resolve(ReturnStock::class);
        foreach ($return->items as $item) {
            $returnStock->handle($return, $item);
        }
    }
}
