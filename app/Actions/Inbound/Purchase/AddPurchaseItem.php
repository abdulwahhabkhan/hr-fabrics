<?php

namespace App\Actions\Inbound\Purchase;

use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use Exception;

final class AddPurchaseItem
{
    /**
     * @throws Exception
     */
    public function handle(Purchase $receipt, array $payload): void
    {
        $product = $payload['product'] ?? null;
        $item_id = $payload['item_id'] ?? null;
        $unit = $payload['unit'] ?? null;
        $qty = $payload['qty'] ?? 0;
        $price = $payload['price'] ?? 0;
        $size = $payload['size'] ?? 0;
        $total_qty = $qty * $size;
        $total = $qty * $price;
        if (mb_strtolower($unit) !== 'box') {
            $total_qty = $payload['total_qty'] ?? 0;
            $total = $total_qty * $price;
            $payload['size'] = 0;
        }
        unset($payload['product'], $payload['item_id']);
        if ($product) {
            $payload['product_id'] = $product['product_id'];
        }

        $payload['total_qty'] = $total_qty;
        $payload['total'] = $total;

        if ($item_id) {
            $item = PurchaseItem::find($item_id);
            $item->update($payload);
        } else {
            $receipt->items()->create($payload);
        }

        resolve(UpdatePurchaseTotal::class)->handle($receipt);
    }
}
