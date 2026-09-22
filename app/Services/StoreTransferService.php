<?php

namespace App\Services;

use App\Actions\LogAction\RecordAction;
use App\Enums\StoreTransferStatus;
use App\Models\Stock\StoreTransfer;
use App\Models\User;

class StoreTransferService
{
    public function unlockStoreTransfer(StoreTransfer $transfer, User $user): void
    {
        resolve(RecordAction::class)->handle($transfer, $user, 'Store Transfer UnLocked');

        $journals = $transfer->journal()->get();
        foreach ($journals as $journal) {
            $journal->transactions()->delete();
        }
        $transfer->journal()->delete();

        $transfer->update(['status' => StoreTransferStatus::Open]);
    }
}
