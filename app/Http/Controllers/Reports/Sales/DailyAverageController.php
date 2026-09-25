<?php

namespace App\Http\Controllers\Reports\Sales;

use App\Http\Controllers\Controller;
use App\Models\Accounts\Journal;
use App\Models\Sales\Order;
use App\Models\Sales\SalesReturn;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DailyAverageController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = [
            'start_date' => Carbon::parse(config('store.session_start')),
            'end_date' => today(),
        ];
        $query_string = $request->only(['start_date', 'end_date']);
        if ($query_string) {
            $filters['start_date'] = Carbon::parse($request->start_date);
            $filters['end_date'] = Carbon::parse($request->end_date);
        }
        $allDays = $filters['start_date']->daysBetween($filters['end_date']);
        $sales = Order::query()
            ->selectRaw('SUM(net_total) as total_sales')
            ->selectRaw('SUM(total_qty) as total_meters')
            ->confirmedBetween(
                $filters['start_date']->toDateString(),
                $filters['end_date']->toDateString()
            )
            ->first();
        $activityDays = Journal::query()
            ->selectRaw('DATE(created_at) as transaction_date')
            ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']])
            ->groupByRaw('transaction_date')
            ->pluck('transaction_date')->toArray();
        $offDays = $allDays->filter(fn (CarbonInterface $day) => ! in_array($day->toDateString(), $activityDays));
        $returns = $this->getReturnTotal($filters);
        $total_amount = $sales->total_sales ?? 0;
        $total_meters = $sales->total_meters ?? 0;

        $total_amount -= $returns['sale']->total_returns ?? 0;

        $fridays = $offDays->filter(fn ($d) => $d->isFriday());
        $holidays = $offDays->filter(fn ($d) => ! $d->isFriday());
        $workingDays = count($activityDays);

        return Inertia::render('Reports/Sales/DailyAverage', [
            'filters' => [
                'start_date' => $filters['start_date']->toDateString(),
                'end_date' => $filters['end_date']->toDateString(),
            ],
            'sale_summary' => [],
            'returns' => $returns,
            'sales' => [
                'sales' => $sales,
            ],
            'total_amount' => $total_amount,
            'total_meters' => $total_meters,
            'holidays' => $holidays->values(),
            'fridays' => $fridays->values(),
            'total_holidays' => $offDays->count(),
            'working_days' => $workingDays,
            'average_per_day' => $workingDays > 0 ? ceil($total_amount / $workingDays) : 0,
        ]);
    }

    /**
     * @return array{sale: SalesReturn|null}
     */
    protected function getReturnTotal(array $dates): array
    {
        $sales = SalesReturn::query()
            ->selectRaw('SUM(total_amount) as total_returns')
            ->selectRaw('SUM(total_qty) as total_meters')
            ->confirmedBetween($dates['start_date'], $dates['end_date'])
            ->withCasts(['total_returns' => 'int'])
            ->first();

        return [
            'sale' => $sales,
        ];
    }
}
