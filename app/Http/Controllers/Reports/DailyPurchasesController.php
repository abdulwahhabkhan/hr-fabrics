<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseReturn;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DailyPurchasesController extends Controller
{
    protected string $sessionKey = 'reports.po.daily';

    public function __invoke(Request $request)
    {
        $filters = [
            'type' => 'city',
            'account' => '',
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
        ];
        $query_string = $request->only(['start_date', 'end_date']);
        if ($query_string) {
            $filters['start_date'] = $request->start_date;
            $filters['end_date'] = $request->end_date;

        }

        $start_date = Carbon::create($filters['start_date'])->startOfDay();
        $end_date = Carbon::create($filters['end_date'])->endOfDay();
        $filters['start_date'] = $start_date->toDateString();
        $filters['end_date'] = $end_date->toDateString();
        $purchases = Purchase::query()
            ->select([
                'bilti_no',
                'created_at',
                'updated_at',
                'supplier_id',
                'invoice_no',
                'total_qty',
                'total',
                'bill_no',
            ])
            ->with([
                'supplier' => fn ($q) => $q->select(['name', 'id']),
            ])
            ->confirmedBetween($start_date, $end_date)
            ->get();

        $purchase_returns = PurchaseReturn::query()
            ->select([
                'bilti_no',
                'created_at',
                'updated_at',
                'supplier_id',
                'invoice_no',
                'total_qty',
                'bill_no',
            ])
            ->selectRaw('(-1*total_amount) as total')
            ->with(['supplier' => fn ($q) => $q->select(['name', 'id'])])
            ->confirmedBetween($start_date, $end_date)
            ->get();

        $purchases = $purchases->concat($purchase_returns)->sortBy('updated_at')->values();

        $purchases_supplier_total = $purchases->groupBy(fn ($row) => $row->supplier->name)
            ->map(
                fn ($row, $key) => [
                    'name' => $key,
                    'total' => $row->sum('total'),
                ])->values();
        $total_purchases = $purchases->sum('total');

        return Inertia::render('Reports/Daily/DailyPurchaseReport',
            [
                'filters' => $filters,
                'rows' => $purchases,
                'total_purchases' => $total_purchases,
                'purchases_supplier_total' => $purchases_supplier_total,
            ]
        );
    }
}
