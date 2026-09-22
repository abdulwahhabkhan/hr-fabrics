<?php

namespace App\Actions\Stock\StoreTransfers;

use App\Actions\Accounts\LedgerEntry;
use App\Enums\JournalHead;
use App\Enums\StoreTransferType;
use App\Models\Stock\StoreTransfer;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class PostStoreTransferLedger
{
    /**
     * @throws Exception
     */
    public function handle(StoreTransfer $transfer, User $user): void
    {
        DB::transaction(function () use ($transfer, $user) {
            $journal = $transfer->journal()->first();
            if ($journal) {
                $journal->transactions()->delete();
                $journal->delete();
            }

            $ledger = resolve(LedgerEntry::class)
                ->setTransactionDate(Carbon::now())
                ->setUser($user)
                ->setHead(JournalHead::Journal)
                ->init($transfer);

            if ($transfer->type === StoreTransferType::Store->value) {
                $ledger->debit($transfer->account_id, $transfer->net_total);
            } else {
                $ledger->credit($transfer->account_id, $transfer->net_total);
            }

            $ledger->logAction();
        });
    }
}
