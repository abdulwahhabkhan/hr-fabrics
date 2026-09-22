<?php

namespace App\Actions\Outbound\SaleOrders;

use App\Models\Accounts\Account;
use App\Models\Sales\Order;

class CreateOrder
{
    public function handle(array $request): Order
    {
        $customer = $request['customer'];
        $invoice_sr = Order::query()->max('invoice_sr');
        $invoice_sr++;
        $invoice_no = date('ym').mb_str_pad($invoice_sr, 3, '0', STR_PAD_LEFT);
        $created_by = request()->user()->id;
        $rate = $customer['customer_id'] ?
            Account::find($customer['customer_id'])->commission_rate : 0;

        return Order::create([
            'created_by' => $created_by,
            'customer_id' => $customer['customer_id'],
            'agent_id' => $customer['agent_id'],
            'discount_rate' => $customer['discount'],
            'discount_type' => $customer['discount_type'],
            'agent_rate' => $rate,
            'invoice_no' => $invoice_no,
            'invoice_sr' => $invoice_sr,
            'payment_mode' => '',
        ]);
    }
}
