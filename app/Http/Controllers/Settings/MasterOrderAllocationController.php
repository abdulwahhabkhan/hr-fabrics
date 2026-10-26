<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Outbound\SaleOrders\MasterAllocateOrder;
use App\Exceptions\UnableToAllocateStockException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Settings\MasterOrderResource;
use App\Models\Accounts\Account;
use App\Models\Purchase\FabricReceiving;
use App\Models\Sales\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Master user tool to book stock for confirmed sale orders that are not fully allocated.
 */
final class MasterOrderAllocationController extends Controller
{
    /**
     * Look up one order by its invoice number. Nothing is listed until a reference is searched.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('masterTools', FabricReceiving::class);
        $refNo = mb_trim((string) $request->string('ref_no'));

        $order = $refNo === '' ? null : Order::query()
            ->select(Order::qCol('*'), Account::qCol('name as customer_name'))
            ->join(Account::tName(), 'customer_id', '=', Account::qCol('id'))
            ->where(Order::qCol('invoice_no'), $refNo)
            ->first();

        return Inertia::render('settings/Master/OrderAllocateIndex', [
            'ref_no' => $refNo,
            'order' => $order ? MasterOrderResource::make($order)->resolve($request) : null,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function allocate(Order $order, Request $request): RedirectResponse
    {
        Gate::authorize('masterAllocate', $order);

        try {
            resolve(MasterAllocateOrder::class)->handle($order, $request->user());
        } catch (UnableToAllocateStockException $e) {
            throw ValidationException::withMessages(['product' => $e->getMessage()]);
        }

        return Redirect::route('settings.master.orders.index', ['ref_no' => $order->invoice_no])
            ->with(['success' => "Order {$order->invoice_no} allocated."]);
    }
}
