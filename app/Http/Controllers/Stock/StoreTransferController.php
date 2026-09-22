<?php

namespace App\Http\Controllers\Stock;

use App\Actions\Stock\StoreTransfers\ConfirmStoreTransfer;
use App\Actions\Stock\StoreTransfers\CreateStoreTransfer;
use App\Actions\Stock\StoreTransfers\DeleteStoreTransferItem;
use App\Actions\Stock\StoreTransfers\GetAvailableStockByCost;
use App\Actions\Stock\StoreTransfers\RecalculateStoreTransferTotals;
use App\Actions\Stock\StoreTransfers\SaveStoreTransferItem;
use App\Actions\Stock\StoreTransfers\SaveStoreTransferLoadedStock;
use App\Enums\PaymentMode;
use App\Enums\StoreTransferStatus;
use App\Enums\StoreTransferType;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesIndexFilters;
use App\Http\Requests\Stock\StoreTransferItemRequest;
use App\Http\Requests\Stock\StoreTransferItemsBulkRequest;
use App\Http\Requests\Stock\StoreTransferRequest;
use App\Http\Requests\Stock\StoreTransferStockRequest;
use App\Http\Resources\Catalog\ProductACResource;
use App\Http\Resources\Stock\StoreTransferItemResource;
use App\Http\Resources\Stock\StoreTransferResource;
use App\Models\Accounts\Account;
use App\Models\Stock\StoreTransfer;
use App\Models\Stock\StoreTransferItem;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class StoreTransferController extends Controller
{
    use HandlesIndexFilters;

    protected string $sessionKey = 'outbound.store-transfers';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {

        $filters = $this->filterSession($request, ['transfer_no', 'store', 'status']);
        $query = StoreTransfer::query();
        $query->select(
            StoreTransfer::qCol('*'),
            Account::qCol('name as account_name'),
            Account::qCol('address->city', false).' as city'
        );
        $query->join(Account::tName(), 'account_id', '=',
            Account::qCol('id'));

        if ($transferNo = $request['transfer_no'] ?? null) {
            $query->whereLike('transfer_no', '%'.$transferNo.'%');
        }
        if ($store = $request['store'] ?? null) {
            $query->whereLike(Account::qCol('name', false), '%'.$store.'%');
        }
        $query->filterWhere(StoreTransfer::qCol('status'), $request['status'] ?? null);

        $query->orderByDesc(StoreTransfer::qCol('updated_at'));
        $data = $query->paginate()->appends($filters);

        return Inertia::render(
            'Stock/StoreTransfers/StoreTransferIndex',
            [
                'rows' => StoreTransferResource::collection($data),
                'filters' => $filters,
                'canAdd' => $request->user()->can('stocks.store-transfers.store'),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render(
            'Stock/StoreTransfers/StoreTransferFormNew',
            [
                'stores' => Account::query()->typeStore()->selectForStoreTransfer()->orderByName()->get(),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransferRequest $request): RedirectResponse
    {
        $transfer = (new CreateStoreTransfer)->handle($request->validated(), $request->user());

        return Redirect::route('stocks.store-transfers.edit', $transfer->id)
            ->with(['success' => 'Store transfer created successfully']);
    }

    /**
     * Display the specified resource.
     */
    public function show(StoreTransfer $storeTransfer): Response
    {
        $data = $storeTransfer->load(['account', 'user', 'items.product']);
        $total_summary = $data->items->groupBy('unit')->map(fn ($item) => $item->sum('qty'));

        return Inertia::render(
            'Stock/StoreTransfers/StoreTransferView',
            [
                'storeTransfer' => $data,
                'total_summary' => $total_summary,
            ]
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StoreTransfer $storeTransfer, ProductService $productService): Response|RedirectResponse
    {
        if ($storeTransfer->is_closed) {
            return Redirect::route('stocks.store-transfers.index')
                ->with(['error' => 'You cannot edit a confirmed store transfer']);
        }

        $data = $storeTransfer->load(['account']);
        $products = $productService->availableProducts();
        $items = StoreTransferItem::getItems($storeTransfer->id);

        return Inertia::render(
            'Stock/StoreTransfers/StoreTransferForm',
            [
                'storeTransfer' => $data,
                'products' => ProductACResource::collection($products),
                'items' => StoreTransferItemResource::collection($items),
                'types' => StoreTransferType::toOptions(),
                'paymentModes' => PaymentMode::toOptions(),
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws Throwable
     */
    public function update(StoreTransferRequest $request, StoreTransfer $storeTransfer): RedirectResponse
    {
        $data = $request->validated();
        $shouldConfirm = ($data['status'] ?? null) === StoreTransferStatus::Closed->value
            && $storeTransfer->is_opened;

        unset($data['status'], $data['account']);
        $storeTransfer->update($data);
        (new RecalculateStoreTransferTotals)->handle($storeTransfer);

        if ($shouldConfirm) {
            (new ConfirmStoreTransfer)->handle($storeTransfer, $request->user());
        }

        return Redirect::route('stocks.store-transfers.index')
            ->with(['success' => 'Store transfer updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StoreTransfer $storeTransfer): void
    {
        //
    }

    public function storeTransferItems(StoreTransfer $storeTransfer): JsonResponse
    {
        $items = StoreTransferItem::getItems($storeTransfer->id);

        return response()->json([
            'items' => StoreTransferItemResource::collection($items),
        ]);
    }

    /**
     * add/update store transfer item
     */
    public function storeTransferItem(StoreTransferItemRequest $request, StoreTransfer $storeTransfer): JsonResponse
    {
        (new SaveStoreTransferItem)->handle($request->validated(), $storeTransfer);
        (new RecalculateStoreTransferTotals)->handle($storeTransfer);
        $items = StoreTransferItem::getItems($storeTransfer->id);

        return response()->json([
            'items' => StoreTransferItemResource::collection($items),
        ]);
    }

    public function deleteStoreTransferItem($id): JsonResponse
    {
        $storeTransfer = (new DeleteStoreTransferItem)->handle($id);
        (new RecalculateStoreTransferTotals)->handle($storeTransfer);
        $items = StoreTransferItem::getItems($storeTransfer->id);

        return response()->json([
            'items' => StoreTransferItemResource::collection($items),
        ]);
    }

    public function availableStock(StoreTransferStockRequest $request, StoreTransfer $storeTransfer): JsonResponse
    {
        $rows = (new GetAvailableStockByCost)->handle(
            (int) $request->validated('product_id'),
            $request->validated('unit')
        );

        return response()->json([
            'rows' => $rows,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function storeTransferItemsBulk(
        StoreTransferItemsBulkRequest $request,
        StoreTransfer $storeTransfer
    ): JsonResponse {
        try {
            (new SaveStoreTransferLoadedStock)->handle($storeTransfer, $request->validated());
        } catch (InsufficientStockException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $items = StoreTransferItem::getItems($storeTransfer->id);

        return response()->json([
            'items' => StoreTransferItemResource::collection($items),
        ]);
    }
}
