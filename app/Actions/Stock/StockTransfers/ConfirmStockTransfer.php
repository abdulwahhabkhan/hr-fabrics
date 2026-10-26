<?php

namespace App\Actions\Stock\StockTransfers;

use App\Actions\Inventory\CheckStockAvailability;
use App\Actions\LogAction\RecordAction;
use App\Enums\PackingType;
use App\Enums\StatusText;
use App\Models\Stock\Inventory;
use App\Models\Stock\StockTransfer;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ConfirmStockTransfer
{
    public function __construct(private readonly CheckStockAvailability $checkStockAvailability) {}

    /**
     * Move the stock: the transferred part of each source lot is booked out to the transfer, and a matching
     * lot is opened at the destination. The new lot keeps the original source and cost, and points to the
     * booked-out line through `parent_id` so the move can be reversed.
     *
     * @throws Throwable
     */
    public function handle(StockTransfer $transfer, User $user): StockTransfer
    {
        if (! $transfer->isOpen()) {
            throw ValidationException::withMessages(['status' => 'Only an open transfer can be confirmed.']);
        }

        DB::transaction(function () use ($transfer, $user) {
            $this->checkStockAvailability->handle(
                $transfer->product_id,
                $transfer->unit,
                $transfer->from_location,
                $transfer->qty,
                $transfer->meters,
                $transfer->size,
            );

            $transactionDate = today();

            foreach ($this->allocate($transfer) as [$lot, $qty, $meters]) {
                $bookedOut = $this->bookOut($lot, $qty, $meters, $transfer);

                $arrived = $bookedOut->replicate();
                $arrived->fill([
                    'location' => $transfer->to_location,
                    'parent_id' => $bookedOut->id,
                    'outbound_type' => null,
                    'outbound_id' => null,
                    'outbound_item_id' => null,
                    'outbound_on' => null,
                    'transaction_date' => $transactionDate,
                ]);
                $arrived->save();
            }

            $transfer->update([
                'status' => StatusText::Close,
                'transaction_date' => $transactionDate,
                'confirmed_at' => now(),
            ]);

            resolve(RecordAction::class)->handle($transfer, $user, 'Stock transfer confirmed');
        });

        return $transfer->refresh();
    }

    /**
     * Pick source lots oldest first. Box and Suit lots are taken by pieces of the transfer size,
     * Thaan lots by meters, with the transfer's thaan count spread over the lots used.
     *
     * @return Collection<int, array{0: Inventory, 1: int, 2: float}>
     */
    private function allocate(StockTransfer $transfer): Collection
    {
        $lots = Inventory::query()
            ->available()
            ->atLocation($transfer->from_location)
            ->where('product_id', $transfer->product_id)
            ->where('unit', $transfer->unit)
            ->when($transfer->unit !== PackingType::Thaan, fn ($query) => $query->where('size', $transfer->size))
            ->fifo()
            ->lockForUpdate()
            ->get();

        $remainingQty = $transfer->qty;
        $remainingMeters = round($transfer->meters, 2);
        $allocations = collect();

        foreach ($lots as $lot) {
            if ($remainingMeters <= 0) {
                break;
            }

            if ($transfer->unit === PackingType::Thaan) {
                $meters = min($remainingMeters, round($lot->meters, 2));
                $qty = min($lot->qty, $remainingQty);
            } else {
                $qty = min($remainingQty, $lot->qty);
                $meters = $qty === $lot->qty ? round($lot->meters, 2) : round($qty * $transfer->size, 2);
            }

            if ($meters <= 0) {
                continue;
            }

            $allocations->push([$lot, $qty, $meters]);
            $remainingQty = max(0, $remainingQty - $qty);
            $remainingMeters = round($remainingMeters - $meters, 2);
        }

        if ($remainingMeters > 0) {
            throw ValidationException::withMessages([
                'qty' => "Not enough stock at {$transfer->from_location->label()} to transfer {$transfer->meters} meters.",
            ]);
        }

        return $allocations;
    }

    /**
     * Book the transferred part of a lot out to the transfer, splitting the lot when only part of it moves.
     */
    private function bookOut(Inventory $lot, int $qty, float $meters, StockTransfer $transfer): Inventory
    {
        $line = $lot;
        if ($meters < round($lot->meters, 2)) {
            $line = $lot->replicate();
            $line->qty = $qty;
            $line->meters = $meters;

            $lot->qty = max(0, $lot->qty - $qty);
            $lot->meters = round($lot->meters - $meters, 2);
            $lot->save();
        }

        $line->qty = $qty;
        $line->outbound()->associate($transfer);
        $line->outbound_item_id = $transfer->id;
        $line->outbound_on = today();
        $line->save();

        return $line;
    }
}
