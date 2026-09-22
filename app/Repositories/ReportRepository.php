<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Models\Accounts\JournalLedger;
use App\Models\Sales\Order;
use App\Models\Sales\SalesReturn;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/** @deprecated */
class ReportRepository
{
    private ?CarbonImmutable $date = null;

    private ?CarbonImmutable $startDate = null;

    private ?CarbonImmutable $endDate = null;

    public function bankBook(CarbonInterface $date): array
    {
        $start_date = $date;
        $filters = ['start_date' => $start_date];
        $accounts = JournalLedger::query()
            ->select(['account_id', 'type', 'name_urdu', 'name'])
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->typeBank()
            ->where('posted_at', '<', $start_date)
            ->groupBy(['account_id'])
            ->get();
        $transactions = JournalLedger::query()
            ->select(['account_id', 'type', 'name_urdu', 'name'])
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->typeBank()
            ->where('posted_at', '>=', $start_date)
            ->groupBy(['account_id'])
            ->get()->keyBy('account_id');

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

    public function bankBookDetail(CarbonInterface $start_date, CarbonInterface $end_date): array
    {

        $filters = ['start_date' => $start_date, 'end_date' => $end_date];
        $accounts = JournalLedger::query()
            ->select(['account_id', 'type', 'name_urdu', 'name'])
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->typeBank()
            ->where('posted_at', '<', $start_date)
            ->groupBy(['account_id'])
            ->get();
        $transactions = JournalLedger::query()
            ->select(['account_id', 'type', 'name_urdu', 'name'])
            ->selectRaw('sum(dr) as total_dr, sum(cr) as total_cr')
            ->typeBank()
            ->where('posted_at', '>=', $start_date)
            ->where('posted_at', '<=', $end_date)
            ->groupBy(['account_id'])
            ->get()->keyBy('account_id');

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

    public function dailySalesSummary($date): array
    {
        $this->date = Carbon::parse($date)->toImmutable();
        $sales = $this->getSalesSummary();

        $sales_total = $sales->map(fn ($data) => $data->sum('net_total'));
        $sales_returns = $this->getSaleReturnSummary();
        $sales_returns_total = $sales_returns->map(fn ($data) => $data->sum('net_total'));
        $total_fresh_sales = $sales_total->sum();
        $gross_sales = $total_fresh_sales;
        $total_sales_returns = $sales_returns_total->sum();
        $net_sales = $gross_sales - $total_sales_returns;

        return [
            'summary_date' => $this->date->format('M d Y'),
            'sales' => $sales,
            'sales_return' => $sales_returns,
            'sales_total' => $sales_total,
            'sales_return_total' => $sales_returns_total,
            'total_fresh_sales' => $total_fresh_sales,
            'gross_sales' => $gross_sales,
            'total_sales_return' => $total_sales_returns,
            'total_net_sales' => $net_sales,
            'total_payment_received' => $this->getTotalPaymentReceived(),
            'total_expenses' => $this->getTotalExpenses(),
        ];
    }

    public function salesSummary(CarbonInterface $start_date, CarbonInterface $end_date): array
    {
        $this->startDate = $start_date;
        $this->endDate = $end_date;
        $sales = $this->getSalesSummary();

        $sales_total = $sales->map(fn ($data) => $data->sum('net_total'));
        $sales_returns = $this->getSaleReturnSummary();
        $sales_returns_total = $sales_returns->map(fn ($data) => $data->sum('net_total'));
        $total_fresh_sales = $sales_total->sum();
        $gross_sales = $total_fresh_sales;
        $total_sales_returns = $sales_returns_total->sum();
        $net_sales = $gross_sales - $total_sales_returns;

        return [
            'summary_date' => $this->startDate->format('M d Y').' - '.$this->endDate->format('M d Y'),
            'sales' => $sales,
            'sales_return' => $sales_returns,
            'sales_total' => $sales_total,
            'sales_return_total' => $sales_returns_total,
            'total_fresh_sales' => $total_fresh_sales,
            'gross_sales' => $gross_sales,
            'total_sales_return' => $total_sales_returns,
            'total_net_sales' => $net_sales,
            'total_payment_received' => $this->getTotalPaymentReceived(),
            'total_expenses' => $this->getTotalExpenses(),
        ];
    }

    private function getSaleReturnSummary(): Collection
    {
        return SalesReturn::query()
            ->with(
                [
                    'customer' => fn (BelongsTo $query) => $query
                        ->select(['id', 'name', 'address->city as city']),
                ]
            )
            ->select([
                'customer_id',
                'invoice_no',
                'id',
                'total_amount as net_total',
                'payment_mode',
            ])
            ->when($this->date, function (Builder $query) {
                $query->confirmedOn($this->date);
            })
            ->when($this->startDate && $this->endDate, function (Builder $query) {
                $query->confirmedBetween($this->startDate, $this->endDate);
            })
            ->get()->groupBy('payment_mode');
    }

    private function getSalesSummary(): Collection
    {
        return Order::query()
            ->select(['net_total', 'payment_mode', 'invoice_no', 'customer_id', 'id'])
            ->where('status', OrderStatus::Close->value)
            ->when($this->date, function (Builder $query) {
                $query->confirmedOn($this->date);
                // $query->where('confirmed_at', $this->date);
            })
            ->when($this->startDate && $this->endDate, function (Builder $query) {
                $query->confirmedBetween($this->startDate, $this->endDate);
                // $query->whereBetween('confirmed_at', [$this->start_date, $this->end_date]);
            })
            ->with(
                [
                    'customer' => fn (BelongsTo $query) => $query
                        ->select(['id', 'name', 'address->city as city']),
                ]
            )
            ->get()->groupBy('payment_mode');
    }

    private function getTotalExpenses()
    {
        if ($this->date) {
            $expenses = AccountRepository::getExpenses($this->date->startOfDay(),
                $this->date->endOfDay());
        } else {
            $expenses = AccountRepository::getExpenses($this->startDate->startOfDay(),
                $this->endDate->endOfDay());
        }

        $total = $expenses->sum('expenses');
        $credit = $expenses->sum('cr');

        return $total - $credit;
    }

    private function getTotalPaymentReceived()
    {
        if ($this->date) {
            return AccountRepository::paymentTotalReceived($this->date->startOfDay(),
                $this->date->endOfDay());
        }

        return AccountRepository::paymentTotalReceived($this->startDate->startOfDay(),
            $this->endDate->endOfDay());

    }
}
