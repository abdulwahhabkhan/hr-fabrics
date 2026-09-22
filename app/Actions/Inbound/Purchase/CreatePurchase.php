<?php

namespace App\Actions\Inbound\Purchase;

use App\Enums\StatusText;
use App\Models\Purchase\FabricReceivingItem;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use App\Models\User;

class CreatePurchase
{
    public function handle(array $data, User $user): Purchase
    {
        $stocks = collect($data['stock']);
        $stock = $stocks->first();

        $receipt = Purchase::create([
            'created_by' => $user->id,
            'stock_id' => $stock['id'],
            'stock_ids' => $stocks->pluck(['id'])->implode(','),
            'supplier_id' => $stock['supplier_id'],
            'invoice_no' => $stocks->pluck(['invoice_no'])->implode(', '),
            'bilti_no' => $stocks->pluck(['bilti_no'])->implode(', '),
            'lot_no' => $stocks->pluck(['lot_no'])->implode(', '),
            'status' => StatusText::Open,
        ]);
        $this->importPurchase($receipt, $stocks->pluck(['id'])->toArray());

        return $receipt;
    }

    public function importPurchase(Purchase $receipt, $stock_ids): void
    {
        /** @var FabricReceivingItem[] $items */
        $items = FabricReceivingItem::query()->whereIn('fabric_receiving_id', $stock_ids)->get();

        foreach ($items as $item) {
            $data = [
                'purchase_id' => $receipt->id,
                'product_id' => $item->product_id,
                'voucher_no' => $item->voucher_no,
                'unit' => $item->unit,
                'size' => $item->size,
                'qty' => $item->qty,
                'price' => 0,
                'total_qty' => $item->total_qty,
                'total' => 0,
            ];

            PurchaseItem::query()->create($data);
        }
    }
}
