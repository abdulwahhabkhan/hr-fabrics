<?php

namespace App\Http\Controllers\Sales\Order;

use App\Actions\Accounts\DeleteJournal;
use App\Actions\LogAction\RecordAction;
use App\Actions\Outbound\SaleReturns\ConfirmSaleReturn;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesIndexFilters;
use App\Http\Requests\Sales\SalesReturnRequest;
use App\Http\Resources\Catalog\ProductACResource;
use App\Http\Resources\Sales\SalesReturnResource;
use App\Models\Accounts\Account;
use App\Models\Sales\SalesReturn;
use App\Services\ProductService;
use DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SalesReturnController extends Controller
{
    use HandlesIndexFilters;

    protected string $sessionKey = 'sales.sor';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $this->filterSession($request, ['ref_no', 'order_no', 'customer_name']);

        $data = SalesReturn::SORList($filters)->paginate()->appends($filters);

        return Inertia::render(
            'Sales/Returns/ReturnIndex',
            [
                'rows' => SalesReturnResource::collection($data),
                'filters' => $filters,
                'canAdd' => $request->user()->can('sales.returns.store'),
                'canView' => $request->user()->can('sales.returns.show'),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $customers = Account::query()
            ->selectForSales()
            ->customers()
            ->orderByName()
            ->get();

        return Inertia::render(
            'Sales/Returns/ReturnFormNew',
            [
                'customers' => $customers,
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     *
     * @throws Throwable
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:accounts,id'],
            'customer' => ['required', 'array'],
        ]);
        // $order = SalesReturn::saveSOR($validate);
        $customer = $data['customer'];
        unset($data['customer']);
        $invoice_sr = SalesReturn::query()->max('sr');
        $invoice_sr++;
        $invoice_no = 'SOR-'.date('ym').mb_str_pad((string) $invoice_sr, 3, '0', STR_PAD_LEFT);
        $created_by = auth()->user()->id;
        $data['created_by'] = $created_by;
        $data['customer_id'] = $customer['customer_id'];
        $rate = $customer['customer_id'] ? Account::find($customer['customer_id'])->commission_rate : 0;
        $data['invoice_no'] = $invoice_no;
        $data['sr'] = $invoice_sr;
        $data['agent_id'] = $customer['agent_id'];
        $data['agent_rate'] = $rate;
        $data['order_no'] = '';

        $order = SalesReturn::create($data);

        return Redirect::route('sales.returns.edit', $order->id)
            ->with(['success' => 'Sales Return created successfully']);
    }

    /**
     * Display the specified resource.
     */
    public function show(SalesReturn $return): Response
    {
        $total_summary = $return->returnItems->groupBy('unit')->map(fn ($item) => $item->sum('qty'));

        return Inertia::render(
            'Sales/Returns/ReturnView',
            [
                'file_info' => $return->info['file'] ?? null,
                'total_summary' => $total_summary,
                'transaction_date' => $return->transaction_display_date->toDateString(),
                'balance' => $return->balance,
                'so_return' => $return->load('customer'),
                'net_balance' => $return->balance - $return->total_amount,
            ]
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SalesReturn $return, ProductService $service): Response
    {
        $products = $service->autocompleteProducts();
        $fileInfo = $return->info['file'] ?? null;
        $fileInfo['thumbnail_url'] = generate_thumbnail($fileInfo['file_path'] ?? null);

        return Inertia::render(
            'Sales/Returns/ReturnForm',
            [
                'return' => $return->load('customer'),
                'file_info' => $fileInfo,
                'products' => ProductACResource::collection($products),
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     *
     *
     * @throws Throwable
     */
    public function update(SalesReturnRequest $request, SalesReturn $return): RedirectResponse
    {
        $data = $request->validated();
        $items = $data['items'];
        unset($data['items']);
        $total = SalesReturn::getTotal($items);
        $data['total_qty'] = $total['total_qty'];
        $data['amount'] = $total['total_amount'];
        $data['total_amount'] = $total['total_amount'] - $data['discount'] + $data['expenses'];
        $data['commission'] = $total['total_commission'];
        $return->fill($data);
        if ($return->isClosed() && $return->transaction_date === null) {
            $return->transaction_date = today();
        }
        $returnItems = [];
        foreach ($items as $item) {
            $returnItems[] = [
                'sales_return_id' => $return->id,
                'product_id' => $item['product']['product_id'],
                'unit' => $item['unit'],
                'size' => $item['size'],
                'commission' => $item['commission'] ?? '',
                'total_commission' => $item['total_commission'] ?? 0,
                'qty' => $item['qty'],
                'rate' => $item['rate'],
                'total_qty' => $item['total_qty'],
                'total_amount' => $item['total_amount'] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($return, $returnItems) {
            $return->save();
            $return->returnItems()->delete();
            if ($returnItems) {
                $return->returnItems()->insert($returnItems);
            }
            // SalesReturn::processTransaction($return);
            if ($return->isClosed()) {
                resolve(ConfirmSaleReturn::class)->handle($return, auth()->user());
            }
        });

        return Redirect::route('sales.returns.index')
            ->with(['success' => 'Sales Return updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     *
     *
     * @throws Throwable
     */
    public function destroy(SalesReturn $return, Request $request): RedirectResponse
    {
        DB::transaction(function () use ($return, $request) {
            $return->delete();
            $return->inventories()->delete();
            resolve(DeleteJournal::class)->handle($return);
            resolve(RecordAction::class)->handle($return, $request->user(), 'Sales Return Deleted');
        });

        return Redirect::route('sales.returns.index')
            ->with(['success' => 'Sales Return deleted successfully']);
    }
}
