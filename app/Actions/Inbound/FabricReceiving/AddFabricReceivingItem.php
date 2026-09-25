<?php

namespace App\Actions\Inbound\FabricReceiving;

use App\Enums\PackingType;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;
use Illuminate\Http\Request;

final class AddFabricReceivingItem
{
    public function handle(Request $request, FabricReceiving $stock): FabricReceivingItem
    {
        $product = $request->input('product');
        $item_id = $request->input('item_id');
        $data = $request->only([
            'qty', 'unit', 'size', 'voucher_no', 'total_qty',
            'size',
        ]);
        ['unit' => $unit, 'qty' => $qty] = $data;

        if (mb_strtolower($unit) !== mb_strtolower(PackingType::Thaan->value)) {
            $data['total_qty'] = $qty * ($data['size'] ?? 1);
        } else {
            $data['size'] = 0;
        }

        if ($product) {
            $data['product_id'] = $product['product_id'];
        }
        $data['fabric_receiving_id'] = $stock->id;

        /** @var FabricReceivingItem|null $item */
        $item = FabricReceivingItem::find($item_id);

        if ($item) {
            $item->update($data);

            return $item;
        }

        return FabricReceivingItem::create($data);
    }
}
