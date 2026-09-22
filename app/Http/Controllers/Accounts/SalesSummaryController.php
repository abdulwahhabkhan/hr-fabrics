<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalesSummaryController extends Controller
{
    public function __invoke(Request $request, ReportService $service, AccountService $accountService): Response
    {
        $date = $request->input('date', '');
        if ($date) {
            $date = Carbon::parse(str($date)->explode('T')->first())->toImmutable();
        } else {
            $date = today();
        }
        $startDate = $date->startOfDay();
        $endDate = $date->endOfDay();
        $sales = $service->getSalesSummary($startDate, $endDate)
            ->with('customer:id,name,address->city as city')
            ->get()
            ->groupBy('payment_mode');
        $salesTotal = $sales->map(fn ($data) => $data->sum('net_total'));
        $saleReturns = $service->getSaleReturnSummary($startDate, $endDate)
            ->with('customer:id,name,address->city as city')
            ->get()->groupBy('payment_mode');
        $saleReturnsTotal = $saleReturns->map(fn ($data) => $data->sum('net_total'));
        $totalFreshSales = $salesTotal->sum();
        $grossSales = $totalFreshSales;
        $totalSalesReturns = $saleReturnsTotal->sum();
        $netSales = $grossSales - $totalSalesReturns;
        $expenses = $accountService->getExpenses($startDate, $endDate);
        $total = $expenses->sum('expenses');
        $credit = $expenses->sum('cr');

        return Inertia::render(
            'Accounts/Reports/SaleSummary',
            [
                'summary_date' => $date->format('M d Y'),
                'sales' => $sales,
                'sales_return' => $saleReturns,
                'sales_total' => $salesTotal,
                'sales_return_total' => $saleReturnsTotal,
                'total_fresh_sales' => $totalFreshSales,
                'gross_sales' => $grossSales,
                'total_sales_return' => $totalSalesReturns,
                'total_net_sales' => $netSales,
                'total_payment_received' => $accountService->getTotalPaymentReceived($startDate, $endDate),
                'total_expenses' => ($total - $credit),
            ]
        );
    }
}
