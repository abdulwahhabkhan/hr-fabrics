<?php

namespace App\Http\Controllers\Purchases\FabricReceiving;

use App\Actions\Inbound\FabricReceiving\AddFabricReceivingItem;
use App\Http\Controllers\Controller;
use App\Http\Resources\Purchases\FabricReceivingItemResource;
use App\Models\Purchase\FabricReceiving;
use Illuminate\Http\Request;

class AddFabricReceivingItemController extends Controller
{
    public function __invoke(FabricReceiving $fabric_receiving, Request $request, AddFabricReceivingItem $addStockItem)
    {
        $this->authorize('edit', $fabric_receiving);
        $addStockItem->handle($request, $fabric_receiving);

        $items = $fabric_receiving->itemsWithProduct()->get();

        return response()->json([
            'items' => FabricReceivingItemResource::collection($items),
        ]);
    }
}
