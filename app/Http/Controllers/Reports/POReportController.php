<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesIndexFilters;
use App\Http\Resources\Reports\PurchasesResource;
use App\Models\Accounts\Account;
use App\Models\Catalog\Product;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;
use App\Models\Purchase\Purchase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class POReportController extends Controller
{
    use HandlesIndexFilters;

    protected string $sessionKey = 'reports.pos';

    public function purchases(Request $request): Response
    {
        $filters = $this->filterSession($request,
            ['supplier', 'invoice_no', 'product_name', 'brand', 'type', 'finish', 'bilti_no', 'bill_no']);

        $data = $this->getData($filters);

        return Inertia::render('Reports/PO/PurchaseReport',
            [
                'filters' => $filters,
                'rows' => PurchasesResource::collection($data),
            ]);
    }

    private function getData($filters = [])
    {
        $purchases = FabricReceiving::query();

        $items = FabricReceivingItem::query()
            ->select([
                'fabric_receiving_items.id as item_id',
                'product_id', 'name as product_name', 'unit', 'finish', 'is_box',
                'fabric_receiving_id', 'voucher_no', 'total_qty', 'fabric_receiving_items.size as size', 'qty',
            ])
            ->join(
                Product::tName(),
                FabricReceivingItem::qCol('product_id'), '=', Product::qCol('id')
            );
        if (! empty($filters['finish'])) {
            $items->where('finish', 'like', '%'.$filters['finish'].'%');
        }

        $purchases->joinSub($items, 'items', function ($join) {
            $join->on('id', '=', 'items.fabric_receiving_id');
        });

        $purchases->join(Account::tName(), 'supplier_id', '=', Account::qCol('id'));

        if (! empty($filters['supplier'])) {
            $purchases->where('name', 'like', '%'.$filters['supplier'].'%');
        }

        if (! empty($filters['bilti_no'])) {
            $purchases->where('bilti_no', 'like', '%'.$filters['bilti_no'].'%');
        }

        if (! empty($filters['invoice_no'])) {
            $purchases->where('invoice_no', 'like', '%'.$filters['invoice_no'].'%');
        }

        if (! empty($filters['type'])) {
            $purchases->where('items.unit', '=', $filters['type']);
        }

        if (! empty($filters['product_name'])) {
            $purchases->where('product_name', 'like', $filters['product_name']);
        }

        if (! empty($filters['bill_no'])) {
            $purchases->whereIn(
                FabricReceiving::qCol('id'),
                Purchase::query()->select(['stock_id'])
                    ->where('bill_no', 'like', '%'.$filters['bill_no'].'%')
            );
        }

        return $purchases->paginate(500);
    }
}
