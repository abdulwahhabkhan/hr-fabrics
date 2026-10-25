<?php

namespace App\Http\Controllers\Stock;

use App\Actions\Stock\StockTransfers\ConfirmStockTransfer;
use App\Actions\Stock\StockTransfers\SaveStockTransfer;
use App\Enums\StockLocation;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesIndexFilters;
use App\Http\Requests\Stock\StockTransferRequest;
use App\Http\Resources\Catalog\ProductACResource;
use App\Http\Resources\Stock\StockTransferResource;
use App\Models\Catalog\Product;
use App\Models\Stock\Inventory;
use App\Models\Stock\StockTransfer;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class StockTransferController extends Controller
{
    use HandlesIndexFilters;

    protected string $sessionKey = 'stock.stock-transfers';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $this->filterSession($request, ['transfer_no', 'product', 'location', 'status']);

        $transfers = StockTransfer::query()
            ->with(['product:id,name,finish', 'user:id,name'])
            ->filterContain('transfer_no', $request['transfer_no'] ?? null)
            ->when($request['product'] ?? null, fn ($query, string $product) => $query->whereHas(
                'product',
                fn ($query) => $query->whereLike('name', '%'.$product.'%')
            ))
            ->atLocation(StockLocation::tryFrom((string) ($request['location'] ?? ''))?->value)
            ->filterWhere('status', $request['status'] ?? null)
            ->latest('updated_at')
            ->paginate()
            ->appends($filters);

        return Inertia::render('Stock/StockTransfers/StockTransferIndex', [
            'rows' => StockTransferResource::collection($transfers),
            'filters' => $filters,
            'canAdd' => $request->user()->can('stocks.stock-transfers.store'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ProductService $productService): Response
    {
        return Inertia::render('Stock/StockTransfers/StockTransferForm', [
            'products' => ProductACResource::collection($productService->availableProducts()),
        ]);
    }

    /**
     * Save a draft transfer. Stock moves when the transfer is confirmed.
     */
    public function store(StockTransferRequest $request, SaveStockTransfer $saveStockTransfer): RedirectResponse
    {
        $transfer = $saveStockTransfer->handle($request->validated(), $request->user());

        if ($request->boolean('confirm')) {
            return $this->confirmAndRedirect($transfer, $request);
        }

        return Redirect::route('stocks.stock-transfers.index')
            ->with(['success' => "Stock transfer {$transfer->transfer_no} saved as draft"]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StockTransfer $stockTransfer, ProductService $productService): Response|RedirectResponse
    {
        if (! $stockTransfer->isOpen()) {
            return Redirect::route('stocks.stock-transfers.index')
                ->with(['error' => 'You cannot edit a confirmed stock transfer']);
        }

        return Inertia::render('Stock/StockTransfers/StockTransferForm', [
            'products' => ProductACResource::collection($productService->availableProducts()),
            'transfer' => StockTransferResource::make($stockTransfer->load('product.brand'))->resolve(),
        ]);
    }

    /**
     * Update an open transfer.
     */
    public function update(StockTransferRequest $request, StockTransfer $stockTransfer, SaveStockTransfer $saveStockTransfer): RedirectResponse
    {
        Gate::authorize('update', $stockTransfer);
        $saveStockTransfer->handle($request->validated(), $request->user(), $stockTransfer);

        if ($request->boolean('confirm')) {
            return $this->confirmAndRedirect($stockTransfer, $request);
        }

        return Redirect::route('stocks.stock-transfers.index')
            ->with(['success' => "Stock transfer {$stockTransfer->transfer_no} updated"]);
    }

    /**
     * Move the stock of an open transfer.
     *
     * @throws Throwable
     */
    public function confirm(Request $request, StockTransfer $stockTransfer): RedirectResponse
    {
        return $this->confirmAndRedirect($stockTransfer, $request);
    }

    /**
     * Remove an open transfer.
     */
    public function destroy(StockTransfer $stockTransfer): RedirectResponse
    {
        Gate::authorize('delete', $stockTransfer);
        $stockTransfer->delete();

        return Redirect::route('stocks.stock-transfers.index')
            ->with(['success' => "Stock transfer {$stockTransfer->transfer_no} deleted"]);
    }

    /**
     * Stock on hand of a product at every location, grouped by unit and size, for the transfer form.
     */
    public function availability(Product $product): JsonResponse
    {
        $stock = Inventory::query()
            ->available()
            ->where('product_id', $product->id)
            ->selectRaw('location, unit, size, SUM(qty) as qty, SUM(meters) as meters')
            ->groupBy('location', 'unit', 'size')
            ->orderBy('location')
            ->orderBy('unit')
            ->orderBy('size')
            ->get()
            ->map(fn (Inventory $row): array => [
                'location' => $row->location->value,
                'unit' => $row->unit->value,
                'size' => (float) $row->size,
                'qty' => (int) $row->qty,
                'meters' => round((float) $row->meters, 2),
            ]);

        return response()->json(['stock' => $stock]);
    }

    /**
     * @throws Throwable
     */
    private function confirmAndRedirect(StockTransfer $transfer, Request $request): RedirectResponse
    {
        Gate::authorize('confirm', $transfer);
        resolve(ConfirmStockTransfer::class)->handle($transfer, $request->user());

        return Redirect::route('stocks.stock-transfers.index')
            ->with(['success' => "Stock moved from {$transfer->from_location->label()} to {$transfer->to_location->label()}"]);
    }
}
