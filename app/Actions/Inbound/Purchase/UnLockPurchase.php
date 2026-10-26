<?php

namespace App\Actions\Inbound\Purchase;

use App\Actions\LogAction\RecordAction;
use App\Enums\StatusText;
use App\Models\Accounts\Journal;
use App\Models\Purchase\Purchase;
use App\Models\User;
use DB;
use Illuminate\Support\Collection;
use Throwable;

final class UnLockPurchase
{
    /**
     * Reopen a confirmed voucher: its journal is removed and its receivings go back to pending invoice.
     * The transaction date is kept so the voucher confirms again on the same date.
     *
     * @throws Throwable
     */
    public function handle(Purchase $receipt, User $user): void
    {
        resolve(RecordAction::class)->handle($receipt, $user, 'Purchase UnLocked');

        DB::transaction(function () use ($receipt): void {
            $receipt->update(['status' => StatusText::Open]);
            $receipt->stocks()->update(['invoiced' => false]);
            /** @var Collection<int, Journal> $journals */
            $journals = $receipt->journal()->get();
            foreach ($journals as $journal) {
                $journal->transactions()->delete();
            }
            $receipt->journal()->delete();
        });
    }
}
