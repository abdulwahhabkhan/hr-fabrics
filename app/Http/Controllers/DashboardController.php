<?php

namespace App\Http\Controllers;

use App\Models\Purchase\Purchase;
use App\Models\Sales\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected string $cacheKey = 'dashboard.widgets';

    public function __invoke(Request $request)
    {
        [$sales, $purchases] = $this->getCachedData();

        return Inertia::render('dashboard',
            [
                'sales' => [
                    'net' => (int) $sales->total_amount,
                ],
                'purchases' => [
                    'net' => (int) $purchases->total_amount,
                ],
                'meters' => [
                    'purchase' => (int) $purchases->total_qty,
                    'sales' => [
                        'net' => round($sales->total_qty ?? 0, 2),
                    ],
                ],
            ]);
    }

    public function getCachedData(): array
    {
        $today = today();
        $sales = Order::query()
            ->confirmedOn($today)
            ->selectRaw('SUM(net_total) total_amount, SUM(total_qty) total_qty')
            ->first();

        $purchases = Purchase::query()
            ->confirmedOn($today)
            ->selectRaw('SUM(total) total_amount, SUM(total_qty) total_qty')
            ->first();

        return [$sales, $purchases];
    }
}
