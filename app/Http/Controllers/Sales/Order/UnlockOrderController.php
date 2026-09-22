<?php

namespace App\Http\Controllers\Sales\Order;

use App\Actions\Outbound\SaleOrders\UnLockOrder;
use App\Http\Controllers\Controller;
use App\Models\Sales\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Redirect;
use Throwable;

class UnlockOrderController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(Order $order, Request $request, UnLockOrder $unLockOrder)
    {
        Gate::authorize('unlock', $order);
        $unLockOrder->handle($order, $request->user());

        return Redirect::route('sales.orders.edit', $order)
            ->with(['success' => 'Sales Order unlocked successfully']);
    }
}
