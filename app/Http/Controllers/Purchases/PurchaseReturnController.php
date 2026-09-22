<?php

namespace App\Http\Controllers\Purchases;

use App\Actions\Inbound\Return\ConfirmReturn;
use App\Actions\Inbound\Return\UpdateReturnTotal;
use App\Enums\ReturnStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\AccountsToOptions;
use App\Http\Controllers\Traits\HandlesIndexFilters;
use App\Http\Resources\Catalog\ProductPORResource;
use App\Http\Resources\Purchases\PurchaseReturnResource;
use App\Models\Accounts\Account;
use App\Models\Purchase\PurchaseReturn;
use App\Services\ProductService;
use Auth;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class PurchaseReturnController extends Controller
{
    use AccountsToOptions, HandlesIndexFilters;

    protected string $sessionKey = 'purchase.por';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $this->filterSession($request, ['ref_no', 'bill_no', 'bilti_no', 'supplier_name']);

        $data = PurchaseReturn::query()
            ->select(PurchaseReturn::qCol('*'), Account::qCol('name as supplier_name'))
            ->join(Account::tName(), 'supplier_id', '=', Account::qCol('id'))
            ->filterContain('bill_no', $request['bill_no'] ?? null)
            ->filterContain('bilti_no', $request['bilti_no'] ?? null)
            ->filterContain('invoice_no', $request['ref_no'] ?? null)
            ->filterContain('name', $request['supplier_name'] ?? null)
            ->orderBy(PurchaseReturn::qCol('updated_at'), 'desc')
            ->paginate()->appends($filters);

        return Inertia::render(
            'Purchases/Returns/ReturnIndex',
            [
                'rows' => PurchaseReturnResource::collection($data),
                'filters' => $filters,
                'canAdd' => $request->user()->can('purchases.por.store'),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render(
            'Purchases/Returns/ReturnFormNew',
            [
                'suppliers' => $this->supplierOptions(),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Exception
     * @throws Throwable
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'supplier' => ['required', 'array'],
            'supplier.supplier_id' => ['required', 'integer', 'exists:accounts,id'],
        ]);
        $invoice_sr = PurchaseReturn::query()->max('sr');
        $invoice_sr++;
        $invoice_no = 'POR-'.date('ym').mb_str_pad($invoice_sr, 3, '0', STR_PAD_LEFT);
        $return = new PurchaseReturn();
        $return->supplier_id = $data['supplier']['supplier_id'];
        $return->sr = $invoice_sr;
        $return->bilti_no = '';
        $return->bill_no = '';
        $return->invoice_no = $invoice_no;
        $return->created_by = $request->user()->id;
        $return->save();

        return response()->json([
            'message' => 'The new purchase return created successfully',
            'redirect' => route('purchases.por.edit', $return->id),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseReturn $por): Response
    {
        $this->authorize('view', $por);
        $por->load('items.product');
        $summary_by_unit = $por->items
            ->groupBy('unit')
            ->map(fn ($r, $k) => ['unit' => $k, 'qty' => $r->sum('qty')])
            ->values();

        return Inertia::render(
            'Purchases/Returns/ReturnView',
            [
                'transaction_date' => $por->transaction_display_date->toDateString(),
                'unit_summary' => $summary_by_unit,
                'file_info' => $por->info['file'] ?? null,
                'pr_return' => $por->load('supplier'),
            ]
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseReturn $por): Response
    {
        $this->authorize('update', $por);
        $productService = resolve(ProductService::class);
        $por->load(['supplier:id,name', 'itemsWithProduct']);
        $products = $productService->getPORProducts();

        return Inertia::render(
            'Purchases/Returns/ReturnForm',
            [
                'por' => $por,
                'file_info' => $por->info['file'] ?? null,
                'products' => ProductPORResource::collection($products),
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws Throwable
     */
    public function update(
        Request $request,
        PurchaseReturn $por,
        UpdateReturnTotal $updateReturnTotal
    ): RedirectResponse {
        $this->authorize('update', $por);
        $data = $request->validate(
            [
                'bilti_no' => ['required'],
                'bill_no' => ['required'],
                'expenses' => ['required'],
                'discount' => ['required'],
                'info' => ['nullable'],
                'status' => ['nullable'],

            ]
        );
        DB::transaction(function () use ($data, $por, $updateReturnTotal): void {
            $por->fill($data);
            if ($data['status'] === ReturnStatus::Closed->value && ! $por->transaction_date) {
                $por->transaction_date = today();
            }
            $updateReturnTotal->handle($por);

            if ($data['status'] === ReturnStatus::Closed->value) {
                resolve(ConfirmReturn::class)->handle($por, Auth::user());
            }
        });

        return Redirect::route('purchases.por.index')
            ->with(['success' => 'PO Return updated successfully']);
    }
}
