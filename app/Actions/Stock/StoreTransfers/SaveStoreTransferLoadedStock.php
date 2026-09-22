<?php

namespace App\Actions\Stock\StoreTransfers;

use App\Actions\Inventory\ReturnInventory;
use App\Enums\PackingType;
use App\Exceptions\InsufficientStockException;
use App\Models\Stock\Inventory;
use App\Models\Stock\StoreTransfer;
use Illuminate\Support\Facades\DB;
use Throwable;

class SaveStoreTransferLoadedStock
{
    /**
     * @throws Throwable
     */
    public function handle(StoreTransfer $transfer, array $data): void
    {
        DB::transaction(function () use ($transfer, $data) {
            $expense = (float) ($data['expense'] ?? 0);

            foreach ($data['rows'] as $row) {
                $qty = (int) $row['qty'];
                if ($qty <= 0) {
                    continue;
                }

                $this->saveRow($transfer, $row, $qty, $expense);
            }

            (new RecalculateStoreTransferTotals)->handle($transfer);
        });
    }

    /**
     * @throws Throwable
     */
    protected function saveRow(StoreTransfer $transfer, array $row, int $qty, float $expense): void
    {
        $unit = $row['unit'];
        $size = (float) $row['size'];
        $cost = (float) $row['cost'];
        $isBox = $unit === PackingType::Box->value;
        $totalQty = $isBox || ! isset($row['meters']) ? $qty * $size : (float) $row['meters'];
        $totalAmount = ($isBox ? $qty : $totalQty) * ($cost + $expense);

        $item = $transfer->items()->create([
            'product_id' => $row['product_id'],
            'unit' => $unit,
            'size' => $size,
            'qty' => $qty,
            'price' => $cost,
            'expense' => $expense,
            'total_qty' => $totalQty,
            'total_amount' => $totalAmount,
        ]);

        $this->consumeInventory($transfer, $item->id, (int) $row['product_id'], $unit, $size, $cost, $qty, $totalQty);
    }

    /**
     * @throws Throwable
     */
    protected function consumeInventory(StoreTransfer $transfer, int $itemId, int $productId, string $unit, float $size, float $cost, int $qty, float $meters): void
    {
        $inventories = Inventory::query()
            ->available()
            ->where('product_id', $productId)
            ->where('unit', $unit)
            ->where('size', $size)
            ->where(function ($query) use ($cost) {
                if ($cost > 0) {
                    $query->where('cost', $cost);
                } else {
                    $query->whereNull('cost')->orWhere('cost', 0);
                }
            })
            ->fifo()
            ->lockForUpdate()
            ->get();

        $availableMeters = (float) $inventories->sum('meters');
        if ($meters > $availableMeters) {
            throw InsufficientStockException::insufficientStock(
                productName: '',
                quantity: (int) ceil($meters),
                availableStock: (int) floor($availableMeters)
            );
        }

        $remaining = $qty;
        $remainingMeters = (int) round($meters);

        foreach ($inventories as $inventory) {
            if ($remaining <= 0) {
                break;
            }

            $allocateQty = min($remaining, (int) $inventory->qty);
            if ($allocateQty <= 0) {
                continue;
            }

            $isWholeLot = $allocateQty === (int) $inventory->qty;
            $isLastAllocation = $remaining - $allocateQty <= 0;
            $allocateMeters = match (true) {
                $isWholeLot => (int) round($inventory->meters),
                $isLastAllocation => min($remainingMeters, (int) round($inventory->meters)),
                default => min($remainingMeters, (int) round($allocateQty * $size)),
            };

            resolve(ReturnInventory::class)
                ->setInventory($inventory)
                ->setQuantity($allocateQty)
                ->setMeters($allocateMeters)
                ->setOutBound($transfer)
                ->setOutBoundItemId($itemId)
                ->process();

            $remaining -= $allocateQty;
            $remainingMeters = max(0, $remainingMeters - $allocateMeters);
        }

        if ($remaining > 0) {
            throw InsufficientStockException::insufficientStock(
                productName: '',
                quantity: $qty,
                availableStock: $qty - $remaining
            );
        }
    }
}
