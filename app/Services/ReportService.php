<?php

namespace App\Services;

use App\Models\Sales\Order;
use App\Models\Sales\SalesReturn;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    public function getSalesSummary(CarbonInterface $start_date, CarbonInterface $end_date): Builder
    {
        return Order::query()
            ->select(['net_total', 'payment_mode', 'invoice_no', 'customer_id', 'id'])
            ->confirmedBetween($start_date, $end_date);
    }

    public function getSaleReturnSummary(CarbonInterface $start_date, CarbonInterface $end_date): Builder
    {
        return SalesReturn::query()
            ->select([
                'customer_id',
                'invoice_no',
                'id',
                'total_amount as net_total',
                'payment_mode',
            ])
            ->confirmedBetween($start_date, $end_date);
    }

    public function getTotalExpenses(CarbonInterface $start_date, CarbonInterface $end_date)
    {
        //
    }

    public function getTotalPaymentReceived(CarbonInterface $start_date, CarbonInterface $end_date)
    {
        //
    }
}
