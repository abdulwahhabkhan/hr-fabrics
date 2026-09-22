<?php

namespace App\Actions\Inbound\Purchase;

use App\Actions\Inventory\IssueInventory;
use App\Actions\LogAction\RecordAction;
use App\Enums\PackingType;
use App\Exceptions\UnableToAllocateStockException;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItemReturn;
use App\Models\User;
use DB;
use Throwable;

final class ReturnPurchaseItem
{
    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws UnableToAllocateStockException
     * @throws Throwable
     */
    public function handle(array $payload, User $user): PurchaseItemReturn
    {
        $data = array_intersect_key($payload, array_flip([
            'purchase_id', 'product_id', 'unit', 'size', 'qty', 'price', 'remarks',
        ]));
        $data['purchase_item_id'] = $payload['id'] ?? null;
        $total_qty = $data['qty'];
        if (mb_strtolower($data['unit']) !== 'box') {
            $total_qty = $data['size'] * $data['qty'];
        }
        $data['total_qty'] = $total_qty;
        $data['total'] = $total = $data['price'] * $total_qty;
        $data['created_by'] = $user->id;
        /** @var Purchase $receipt */
        $receipt = Purchase::findOrFail($data['purchase_id']);

        /** @var PurchaseItemReturn $return */
        $return = DB::transaction(function () use ($receipt, $data, $total) {
            Purchase::query()->where('id', $receipt->id)->increment('total_return', $total);
            /** @var PurchaseItemReturn $returnItem */
            $returnItem = PurchaseItemReturn::create($data);

            resolve(IssueInventory::class)
                ->setOutbound($returnItem)
                ->setOutboundItemId($returnItem->id)
                ->setTransactionDate($receipt->transaction_date)
                ->setProductId($returnItem->product_id)
                ->setUnit(PackingType::from($returnItem->unit))
                ->setSize((float) $returnItem->size)
                ->setQuantity((int) $returnItem->qty)
                ->setMeters((float) $returnItem->total_qty)
                ->issue();

            return $returnItem;
        });

        resolve(RecordAction::class)->handle($return, $user, 'POR', ['data' => $return]);

        return $return;
    }
}
