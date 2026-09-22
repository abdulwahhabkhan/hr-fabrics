<?php

namespace App\Actions\Stock\StoreTransfers;

use App\Enums\StoreTransferStatus;
use App\Models\Stock\StoreTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class ConfirmStoreTransfer
{
    /**
     * @throws Throwable
     */
    public function handle(StoreTransfer $transfer, User $user): StoreTransfer
    {
        DB::transaction(function () use ($transfer, $user) {
            $transfer->update([
                'status' => StoreTransferStatus::Closed,
                'confirmed_at' => today(),
            ]);
            (new PostStoreTransferLedger)->handle($transfer, $user);
        });

        return $transfer->fresh();
    }
}
