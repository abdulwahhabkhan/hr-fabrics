<?php

namespace App\Http\Controllers\Purchases;

use App\Actions\Inbound\Purchase\AddPurchaseItem;
use App\Actions\Inbound\Purchase\ConfirmPurchaseActions;
use App\Actions\Inbound\Purchase\CreatePurchase;
use App\Actions\Inbound\Purchase\ReturnPurchaseItem;
use App\Actions\Inbound\Purchase\UpdatePurchaseTotal;
use App\Actions\LogAction\RecordAction;
use App\Enums\StatusText;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesIndexFilters;
use App\Http\Resources\Catalog\ProductACResource;
use App\Http\Resources\Purchases\PurchaseItemResource;
use App\Http\Resources\Purchases\PurchaseResource;
use App\Http\Resources\Sales\OrderItemResource;
use App\Models\Accounts\Account;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use App\Services\ProductService;
use Auth;
use DB;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class PurchaseController extends Controller
{
    use HandlesIndexFilters;

    protected string $sessionKey = 'purchase.purchases';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $this->filterSession($request, ['ref_no', 'supplier_name', 'bill_no', 'bilti_no', 'lot_no']);

        $query = Purchase::query();
        $query->select(Purchase::qCol('*'));
        $query->addSelect(Account::qCol('name  as supplier_name'));
        $query->join(Account::tName(), 'supplier_id', '=', Account::qCol('id'));
        $query->filterContain('invoice_no', $request['ref_no'] ?? null);
        $query->filterContain('bill_no', $request['bill_no'] ?? null);
        $query->filterContain('bill_no', $request['bill_no'] ?? null);
        $query->filterContain('bilti_no', $request['bilti_no'] ?? null);
        $query->filterContain('name', $request['supplier_name'] ?? null);

        $query->orderBy(Purchase::qCol('updated_at'), 'desc');
        $data = $query
            ->paginate()
            ->appends($filters);

        return Inertia::render(
            'Purchases/Purchases/PurchaseIndex',
            [
                'rows' => PurchaseResource::collection($data),
                'filters' => $filters,
                'canAdd' => $request->user()->can('purchases.pos.store'),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     *
     * @throws AuthorizationException
     */
    public function create(): Response
    {

        $stocks = FabricReceiving::query()->confirmed()->invoiced(false)->get();

        return Inertia::render(
            'Purchases/Purchases/PurchaseFormNew',
            [
                'stocks' => $stocks,
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return RedirectResponse|void
     *
     * @throws Throwable
     */
    public function store(Request $request)
    {
        $validated = $request->validate(
            [
                'stock.*.id' => 'required|exists:fabric_receivings,id|unique:purchases,stock_id',
                'stock.*.supplier_id' => 'required',
                'stock.*.bilti_no' => 'required',
                'stock.*.invoice_no' => 'required',
                'stock.*.lot_no' => 'sometimes|nullable',
            ],
            ['stock.*.id.unique' => 'Receiving already converted to voucher']
        );
        if ($validated) {
            return DB::transaction(function () use ($request) {
                $receipt = resolve(CreatePurchase::class)->handle($request->all(), $request->user());

                return Redirect::route('purchases.pos.edit', $receipt->id)
                    ->with(['success' => 'Voucher Created Successfully']);
            });
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Purchase $po): Response
    {
        $this->authorize('view', $po);
        $receipt = $po;
        $data = $receipt->load(['supplier', 'items.product']);
        $total_summary = $data->items->groupBy('unit')->map(fn ($item) => $item->sum('qty'));

        return Inertia::render(
            'Purchases/Purchases/PurchaseView',
            [
                'receipt' => $data,
                'transaction_date' => $data->transaction_display_date,
                'total_summary' => $total_summary,
            ]
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $po): Response
    {
        $this->authorize('update', $po);
        $receipt = $po;
        $data = $receipt->load(['supplier']);

        $data['shipped'] = '';
        $items = $receipt->itemsWithProduct()->get();

        return Inertia::render(
            'Purchases/Purchases/PurchaseForm',
            [
                'receipt' => $data,
                'products' => ProductACResource::collection(resolve(ProductService::class)->autocompleteProducts()),
                'items' => PurchaseItemResource::collection($items),
                'status_open' => StatusText::Open,
                'status_close' => StatusText::Close,
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws Exception
     * @throws Throwable
     */
    public function update(Request $request, Purchase $po): RedirectResponse
    {
        $this->authorize('update', $po);
        DB::transaction(function () use ($request, $po) {
            $data = $request->only('discount', 'bill_no', 'status', 'remarks', 'lot_no');
            $po->fill($data);
            if ($po->isClosed() && ! $po->transaction_date) {
                $po->transaction_date = today();
            }
            $po->save();
            resolve(UpdatePurchaseTotal::class)->handle($po);
            if ($po->isClosed()) {
                resolve(ConfirmPurchaseActions::class)->handle($po, Auth::user());
            }
        });

        return Redirect::route('purchases.pos.index')
            ->with(['success' => 'Voucher updated Successfully']);
    }

    /**
     * Remove the specified resource from storage.
     *
     *
     * @throws Throwable
     */
    public function destroy(Purchase $po, Request $request): RedirectResponse
    {
        $this->authorize('delete', $po);
        DB::transaction(function () use ($po) {
            FabricReceiving::query()
                ->whereIn('id', str($po->stock_ids)->explode(','))
                ->update(['invoiced' => false]);
            $po->items()->delete();
            $po->delete();
            resolve(RecordAction::class)->handle($po, Auth::user(), 'Receipt Deleted');
        });

        return Redirect::route('purchases.pos.index')
            ->with(['success' => 'Voucher Deleted Successfully']);
    }

    public function orderItems(Purchase $order): JsonResponse
    {
        $items = $order->itemsWithProduct()->get();

        return response()->json([
            'items' => OrderItemResource::collection($items),
        ]);
    }

    /**
     * Add/update order item
     *
     * @throws Exception
     */
    public function orderItem(Request $request, Purchase $order): JsonResponse
    {
        resolve(AddPurchaseItem::class)->handle($order, $request->validate([
            'item_id' => ['sometimes', 'integer'],
            'voucher_no' => ['required'],
            'product' => ['sometimes', 'array'],
            'product_id' => ['required_if:product,null'],
            'unit' => ['required'],
            'price' => ['required'],
            'qty' => ['required'],
            'size' => ['nullable'],
            'total_qty' => ['nullable'],
        ]));

        $items = $order->itemsWithProduct()->get();

        return response()->json([
            'items' => PurchaseItemResource::collection($items),
        ]);
    }

    /**
     * @throws Exception
     */
    public function deleteOrderItem($id): JsonResponse
    {
        $item = PurchaseItem::findOrFail($id);
        $receipt = $item->purchase;
        $item->delete();
        resolve(UpdatePurchaseTotal::class)->handle($receipt);

        $items = $receipt->itemsWithProduct()->get();

        return response()->json([
            'items' => PurchaseItemResource::collection($items),
        ]);
    }

    /**
     * Return PO item
     */
    public function itemReturn(Request $request): RedirectResponse
    {
        $id = $request->input('purchase_id');
        $validate = $request->validate([
            'purchase_id' => 'required',
            'id' => [
                'required',
                'exists:purchase_items',
            ],
            'product_id' => 'required',
            'name' => 'required',
            'unit' => 'required',
            'size' => 'required',
            'qty' => 'required',
            'price' => 'required',
        ]);

        if ($validate) {
            $payload = $validate + $request->only('remarks');
            resolve(ReturnPurchaseItem::class)->handle($payload, $request->user());

            return Redirect::route('purchases.pos.show', $id)
                ->with(['success' => 'Voucher item returned successfully']);
        }

        return Redirect::route('purchases.pos.show', $id)
            ->with(['error' => 'Voucher item returned successfully']);

    }
}
