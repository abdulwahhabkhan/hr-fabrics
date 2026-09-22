<?php

namespace App\Http\Controllers\Sales\Order;

use App\Actions\Outbound\SaleOrders\UpdateOrderTotal;
use App\Enums\PackingType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Sales\OrderItemResource;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Rules\CheckInventoryRule;
use App\Services\OrderService;
use DB;
use Exception;
use Illuminate\Http\Request;
use Throwable;

class AddOrderItemController extends Controller
{
    protected OrderService $orderService;

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(
        Order $order,
        Request $request,
        OrderService $orderService,
        UpdateOrderTotal $updateOrderTotal
    ) {
        $this->orderService = $orderService;
        /*$id = $order->id;
        $verifyQty = ['required', 'numeric', new CheckInventoryRule];
        */
        $validated = $request->validate([
            'item_id' => ['integer'],
            'product' => ['required', 'array'],
            'unit' => ['required', 'string'],
            'size' => ['required', 'numeric'],
            'qty' => ['required', 'numeric', new CheckInventoryRule],
            'price' => ['required', 'numeric'],
            'commission' => ['nullable'],
            'discount' => ['required', 'numeric'],

        ]);
        unset($validated['over_sold']);
        DB::transaction(function () use ($updateOrderTotal, $validated, $order) {
            $this->addItem($validated, $order);
            $updateOrderTotal->handle($order);

        });

        $items = $order->itemsWithProduct()->get(); // OrderItem::getItems($id);

        return response()->json([
            'items' => OrderItemResource::collection($items),
        ]);
    }

    protected function addItem(array $data, Order $order)
    {
        $item_id = $data['item_id'] ?? '';

        $data['product_id'] = $data['product']['product_id'];

        $data['total_qty'] = $data['qty'] * ($data['size'] ?? 1);
        if ($data['unit'] === PackingType::Box->name) {
            $data['total_amount'] = $data['qty'] * $data['price'];
        } elseif ($data['unit'] === PackingType::Suit->name) {
            $data['total_amount'] = $data['qty'] * $data['price'];
        } else {
            $data['total_amount'] = $data['total_qty'] * $data['price'];
        }
        if ($order->isDiscountPerMeter()) {
            $discount = $data['total_qty'] * $data['discount'];
        } else {
            $discount = $data['total_amount'] * ($data['discount'] / 100);
        }
        $data['discount'] = $discount;
        $data['total_commission'] = $this->orderService->getAgentCommission($data);
        $data['commission'] ??= 0;
        unset($data['product']);
        unset($data['item_id']);

        if ($item_id) {
            $item = OrderItem::find($item_id);
            $item->update($data);

            return $item;
        }

        return $order->items()->create($data);
    }
}
