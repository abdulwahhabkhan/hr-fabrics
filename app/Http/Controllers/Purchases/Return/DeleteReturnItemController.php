<?php

namespace App\Http\Controllers\Purchases\Return;

use App\Actions\Inbound\Return\UpdateReturnTotal;
use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Purchase\PurchaseReturnItem;
use DB;
use Throwable;

class DeleteReturnItemController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(PurchaseReturn $return, PurchaseReturnItem $item, UpdateReturnTotal $updateReturnTotal)
    {

        $this->authorize('update', $return);
        DB::transaction(function () use ($return, $updateReturnTotal, $item) {

            $return->inventories()->where('outbound_item_id', $item->id)
                ->update([
                    'outbound_item_id' => null,
                    'outbound_id' => null,
                    'outbound_type' => null,
                ]);
            $item->delete();
            $updateReturnTotal->handle($return);
        });

        return response()->json([
            'items' => $return->itemsWithProduct()->get(),
        ]);
    }
}
