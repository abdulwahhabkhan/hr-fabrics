<?php

namespace App\Http\Resources\Settings;

use App\Models\Accounts\Account;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\Purchase;
use App\Models\Sales\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * A confirmed receiving with the purchase vouchers and sale orders that depend on it.
 *
 * @mixin FabricReceiving
 *
 * @property FabricReceiving $resource
 */
final class MasterFabricReceivingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $reopen = Gate::forUser($request->user())->inspect('masterReopen', $this->resource);

        return [
            'id' => $this->id,
            'invoice_no' => $this->invoice_no,
            'bilti_no' => $this->bilti_no,
            'lot_no' => $this->lot_no,
            'supplier_name' => $this->supplier_name,
            'total_qty' => $this->total_qty,
            'total_meters' => $this->total_meters,
            'status' => $this->status,
            'invoiced' => $this->invoiced,
            'transaction_date' => $this->transaction_display_date->toDateString(),
            'purchases' => $this->resource->purchases()->get()->map(fn (Purchase $purchase): array => [
                'id' => $purchase->id,
                'bill_no' => $purchase->bill_no,
                'invoice_no' => $purchase->invoice_no,
                'total' => $purchase->total,
                'status' => $purchase->status,
                'transaction_date' => $purchase->transaction_display_date->toDateString(),
            ]),
            'sale_orders' => $this->resource->saleOrders()
                ->select(Order::qCol('*'), Account::qCol('name as customer_name'))
                ->join(Account::tName(), 'customer_id', '=', Account::qCol('id'))
                ->orderBy(Order::qCol('transaction_date'))
                ->get()
                ->map(fn (Order $order): array => [
                    'id' => $order->id,
                    'invoice_no' => $order->invoice_no,
                    'customer_name' => $order->customer_name,
                    'net_total' => $order->net_total,
                    'status' => $order->status,
                    'transaction_date' => $order->transaction_date?->toDateString(),
                ]),
            'can_reopen' => $reopen->allowed(),
            'reopen_blocked_reason' => $reopen->denied() ? $reopen->message() : null,
        ];
    }
}
