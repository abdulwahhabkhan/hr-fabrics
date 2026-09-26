<?php

namespace App\Services;

use App\Models\Accounts\JournalLedger;
use App\Models\Sales\Order;
use App\Models\Sales\SalesReturn;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    public function getSalesSummary(
        CarbonImmutable $start_date,
        CarbonImmutable $end_date
    ): Builder {
        return Order::query()
            ->select(['net_total', 'payment_mode', 'invoice_no', 'customer_id', 'id'])
            ->confirmedBetween($start_date, $end_date);
    }

    public function getSaleReturnSummary(CarbonImmutable $start_date, CarbonImmutable $end_date): Builder
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

    public function bankBookDetail(CarbonImmutable $start_date, CarbonImmutable $end_date): array
    {

        $filters = ['start_date' => $start_date, 'end_date' => $end_date];
        $accounts = JournalLedger::query()
            ->select(['account_id', 'type', 'name_urdu', 'name'])
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->typeBank()
            ->where('posted_at', '<', $start_date)
            ->groupBy('account_id')
            ->get();
        $transactions = JournalLedger::query()
            ->select(['account_id', 'type', 'name_urdu', 'name'])
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->typeBank()
            ->where('posted_at', '>=', $start_date)
            ->where('posted_at', '<=', $end_date)
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $banks = $accounts->map(function ($row) use ($transactions) {
            $debit = $credit = 0;
            $transaction = $transactions[$row['account_id']] ?? [];
            if ($transaction) {
                $debit = $transaction->total_dr;
                $credit = $transaction->total_cr;
            }
            $opening_balance = $row->total_dr - $row->total_cr;
            $closing_balance = $opening_balance + ($debit - $credit);
            $row->debit = $debit;
            $row->credit = $credit;
            $row->opening_balance = $opening_balance;
            $row->closing_balance = $closing_balance;

            return $row;
        });
        $totals = [
            'opening_balance' => $banks->sum('opening_balance'),
            'debit' => $banks->sum('debit'),
            'credit' => $banks->sum('credit'),
            'closing_balance' => $banks->sum('closing_balance'),
        ];

        return [
            'filters' => $filters,
            'rows' => $banks,
            'totals' => $totals,
        ];
    }

    public function salesSummary(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {

        $sales = $this->getSalesSummary($startDate, $endDate)
            ->with('customer:id,name,address->city as city')
            ->get()
            ->groupBy('payment_mode');

        $sales_total = $sales->map(fn ($data) => $data->sum('net_total'));
        $sales_returns = $this->getSaleReturnSummary($startDate, $endDate)
            ->with('customer:id,name,address->city as city')
            ->get()
            ->groupBy('payment_mode');
        $sales_returns_total = $sales_returns->map(fn ($data) => $data->sum('net_total'));
        $total_fresh_sales = $sales_total->sum();
        $gross_sales = $total_fresh_sales;
        $total_sales_returns = $sales_returns_total->sum();
        $net_sales = $gross_sales - $total_sales_returns;

        return [
            'summary_date' => $startDate->displayDate().' - '.$endDate->displayDate(),
            'sales' => $sales,
            'sales_return' => $sales_returns,
            'sales_total' => $sales_total,
            'sales_return_total' => $sales_returns_total,
            'total_fresh_sales' => $total_fresh_sales,
            'gross_sales' => $gross_sales,
            'total_sales_return' => $total_sales_returns,
            'total_net_sales' => $net_sales,
            'total_payment_received' => $this->getTotalPaymentReceived($startDate, $endDate),
            'total_expenses' => $this->getTotalExpenses($startDate, $endDate),
        ];
    }

    private function getTotalPaymentReceived(CarbonImmutable $startDate, CarbonImmutable $endDate)
    {
        return resolve(AccountService::class)->getTotalPaymentReceived($startDate->startOfDay(),
            $endDate->endOfDay());

    }

    private function getTotalExpenses(CarbonImmutable $startDate, CarbonImmutable $endDate): float
    {

        $expenses = resolve(AccountService::class)->getExpenses($startDate->startOfDay(),
            $endDate->endOfDay());

        $total = $expenses->sum('expenses');
        $credit = $expenses->sum('cr');

        return $total - $credit;
    }
}
