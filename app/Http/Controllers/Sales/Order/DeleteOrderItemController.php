<?php

namespace App\Http\Controllers\Sales\Order;

use App\Actions\Outbound\SaleOrders\UpdateOrderTotal;
use App\Http\Controllers\Controller;
use App\Http\Resources\Sales\OrderItemResource;
use App\Models\Sales\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeleteOrderItemController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(OrderItem $item, Request $request, UpdateOrderTotal $updateOrderTotal)
    {
        $order = $item->order;
        $this->authorize('update', $order);

        return DB::transaction(function () use ($item, $order, $updateOrderTotal) {
            $item->delete();
            $updateOrderTotal->handle($order);
            $items = $order->itemsWithProduct()->get(); // OrderItem::getItems($item->order_id);

            return response()->json([
                'items' => OrderItemResource::collection($items),
            ]);
        });

    }
}
