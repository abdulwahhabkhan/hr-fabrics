<?php

namespace App\Actions\Inbound\Purchase;

use App\Actions\Accounts\LedgerEntry;
use App\Enums\JournalHead;
use App\Models\Purchase\Purchase;
use App\Models\User;
use Exception;

final class ProcessPurchaseLedger
{
    /**
     * @throws Exception
     */
    public function handle(Purchase $receipt, User $user): void
    {
        resolve(LedgerEntry::class)
            ->setTransactionDate($receipt->transaction_date)
            ->setUser($user)
            ->setHead(JournalHead::Purchases)
            ->init($receipt)
            ->credit($receipt->supplier_id, $receipt->total)
            ->logAction();
    }
}
