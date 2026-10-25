<?php

namespace App\Actions\Stock\StockTransfers;

use App\Actions\LogAction\RecordAction;
use App\Enums\StatusText;
use App\Models\Stock\Inventory;
use App\Models\Stock\StockTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class UnlockStockTransfer
{
    /**
     * Reverse a confirmed transfer: remove the lots opened at the destination and release the source lines.
     * Refused once any of the moved stock has been sold, returned or transferred again.
     *
     * @throws Throwable
     */
    public function handle(StockTransfer $transfer, User $user): StockTransfer
    {
        if (! $transfer->isClosed()) {
            throw ValidationException::withMessages(['status' => 'Only a confirmed transfer can be unlocked.']);
        }

        DB::transaction(function () use ($transfer, $user) {
            $sourceLineIds = $transfer->inventories()->pluck('id');
            $arrivedLots = Inventory::query()->whereIn('parent_id', $sourceLineIds)->lockForUpdate()->get();

            if ($arrivedLots->contains(fn (Inventory $lot) => $lot->outbound_id !== null)) {
                throw ValidationException::withMessages([
                    'status' => "Stock moved to {$transfer->to_location->label()} has already been used, the transfer cannot be unlocked.",
                ]);
            }

            Inventory::query()->whereIn('id', $arrivedLots->modelKeys())->delete();
            $transfer->inventories()->update([
                'outbound_type' => null,
                'outbound_id' => null,
                'outbound_item_id' => null,
                'outbound_on' => null,
            ]);

            $transfer->update([
                'status' => StatusText::Open,
                'transaction_date' => null,
                'confirmed_at' => null,
            ]);

            resolve(RecordAction::class)->handle($transfer, $user, 'Stock transfer unlocked');
        });

        return $transfer->refresh();
    }
}
