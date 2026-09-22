<?php

namespace App\Actions\Inbound\Return;

use App\Actions\Inventory\ReturnInventory;
use App\Enums\PackingType;
use App\Exceptions\InsufficientStockException;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Purchase\PurchaseReturnItem;
use App\Models\Stock\Inventory;
use Exception;
use Throwable;

final class AddReturnItem
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function handle(PurchaseReturn $return, array $payload): void
    {
        $product = $payload['product'] ?? null;
        $item_id = $payload['item_id'] ?? null;
        $unit = $payload['unit'] ?? null;
        $qty = (int) $payload['qty'];
        $rate = (float) $payload['rate'];
        $size = (float) ($payload['size'] ?? 0);
        $totalQty = (int) ($qty * $size);
        $total = $rate * $totalQty;
        if ($unit === PackingType::Box->name) {
            // For boxes, the amount is simply qty * rate
            $total = $qty * $rate;
        }
        unset($payload['product'], $payload['item_id']);
        if ($product) {
            $payload['product_id'] = $product['product_id'];
        }

        $payload['total_qty'] = $totalQty;
        $payload['total_amount'] = $total;

        // Create or update the return item
        if ($item_id) {
            /** @var PurchaseReturnItem $item */
            $item = PurchaseReturnItem::findOrFail($item_id);
            $item->update($payload);
        } else {
            /** @var PurchaseReturnItem $item */
            $item = $return->items()->create($payload);
        }

        // Allocate inventory lines by product and unit to fulfill the requested qty
        // We allocate based on item qty (number of pieces), not total meters
        $productId = $payload['product_id'] ?? $item->product_id;
        $remaining = $qty;
        if ($remaining <= 0) {
            resolve(UpdateReturnTotal::class)->handle($return);

            return;
        }

        /** @var Inventory[] $inventories */
        $inventories = Inventory::query()
            ->available()
            ->where('product_id', $productId)
            ->where('unit', $unit)
            ->fifo()
            ->lockForUpdate()
            ->get();

        foreach ($inventories as $inv) {
            if ($remaining <= 0) {
                break;
            }
            $allocateQty = min($remaining, (int) $inv->qty);
            if ($allocateQty <= 0) {
                continue;
            }
            $allocateMeters = (int) round($allocateQty * (float) $inv->size);

            // Use ReturnInventory action to split/assign the inventory line
            /** @var Inventory $booked */
            $booked = resolve(ReturnInventory::class)
                ->setInventory($inv)
                ->setQuantity($allocateQty)
                ->setMeters($allocateMeters)
                ->setOutBound($return)
                ->setOutBoundItemId($item->id)
                ->process();

            // If split produced a clone, ensure it has the same base attributes persisted already by process().
            $remaining -= $allocateQty;
        }

        if ($remaining > 0) {
            // Not enough inventories to fulfill the requested quantity
            throw InsufficientStockException::insufficientStock(
                productName: '',
                quantity: $qty,
                availableStock: $qty - $remaining
            );
        }

        resolve(UpdateReturnTotal::class)->handle($return);
    }
}
