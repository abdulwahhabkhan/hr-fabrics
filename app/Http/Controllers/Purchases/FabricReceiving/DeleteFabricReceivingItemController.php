<?php

namespace App\Http\Controllers\Purchases\FabricReceiving;

use App\Actions\Inbound\FabricReceiving\UpdateFabricReceivingTotal;
use App\Http\Controllers\Controller;
use App\Http\Resources\Purchases\FabricReceivingItemResource;
use App\Models\Purchase\FabricReceivingItem;
use Illuminate\Http\Request;

class DeleteFabricReceivingItemController extends Controller
{
    public function __invoke(Request $request, FabricReceivingItem $item, UpdateFabricReceivingTotal $updateStockTotal)
    {
        $stock = $item->fabricReceiving;
        $this->authorize('edit', $stock);
        $item->delete();
        $updateStockTotal->handle($stock);
        $items = $stock->itemsWithProduct()->get();

        return response()->json([
            'items' => FabricReceivingItemResource::collection($items),
        ]);
    }
}
