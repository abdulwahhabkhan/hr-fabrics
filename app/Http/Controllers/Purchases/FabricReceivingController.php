<?php

namespace App\Http\Controllers\Purchases;

use App\Actions\Inbound\FabricReceiving\CreateInbound;
use App\Actions\Inbound\FabricReceiving\FabricReceivingConfirmed;
use App\Actions\Inbound\FabricReceiving\UpdateFabricReceivingTotal;
use App\Actions\LogAction\RecordAction;
use App\Enums\DirectoryType;
use App\Enums\StatusText;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\AccountsToOptions;
use App\Http\Controllers\Traits\HandlesIndexFilters;
use App\Http\Resources\Catalog\ProductACResource;
use App\Http\Resources\Purchases\FabricReceivingItemResource;
use App\Http\Resources\Purchases\FabricReceivingResource;
use App\Models\Accounts\Account;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;
use App\Services\ProductService;
use DB;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class FabricReceivingController extends Controller
{
    use AccountsToOptions, HandlesIndexFilters;

    protected string $sessionKey = 'purchase.fabric_receivings';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $this->filterSession($request, ['bilti_no', 'lot_no', 'supplier_name', 'ref_no', 'invoiced']);
        $invoiced = $request['invoiced'] ?? null;
        $query = FabricReceiving::query();
        $query->select(FabricReceiving::qCol('*'), Account::qCol('name  as supplier_name'));
        $query->join(Account::tName(), 'supplier_id', '=', Account::qCol('id'));
        $query->filterContain('invoice_no', $request['ref_no'] ?? null);
        $query->filterContain('bilti_no', $request['bilti_no'] ?? null);
        $query->filterContain('lot_no', $request['lot_no'] ?? null);
        $query->filterContain('name', $request['supplier_name'] ?? null);
        if ($invoiced !== null) {
            $query->where('invoiced', (bool) $invoiced);
        }
        $query->orderBy(FabricReceiving::qCol('updated_at'), 'desc');
        $data = $query->paginate()->appends($filters);

        return Inertia::render(
            'Purchases/FabricReceivings/FabricReceivingIndex',
            [
                'rows' => FabricReceivingResource::collection($data),
                'filters' => $filters,
                'canAdd' => $request->user()->can('purchases.fabric-receivings.store'),
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
        return Inertia::render(
            'Purchases/FabricReceivings/FabricReceivingFormNew',
            [
                'suppliers' => $this->supplierOptions(),
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Exception
     */
    public function store(Request $request): RedirectResponse
    {
        $order = resolve(CreateInbound::class)->handle($request);

        return Redirect::route('purchases.fabric-receivings.edit', $order->id)
            ->with(['success' => 'Fabric receiving created successfully']);
    }

    /**
     * Display the specified resource.
     */
    public function show(FabricReceiving $fabric_receiving): Response
    {
        $data = $fabric_receiving->load(['supplier', 'items.product']);
        $total_summary = $data->items->groupBy('unit')->map(fn ($item) => $item->sum('qty'));

        return Inertia::render(
            'Purchases/FabricReceivings/FabricReceivingView',
            [
                'order' => $data,
                'transaction_date' => $data->transaction_display_date->toDateString(),
                'attachments' => $fabric_receiving->files()->get(),
                'total_summary' => $total_summary,
            ]
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(FabricReceiving $fabric_receiving)
    {
        $this->authorize('edit', $fabric_receiving);

        return Inertia::render(
            'Purchases/FabricReceivings/FabricReceivingForm',
            [
                'stock' => fn () => $fabric_receiving->load(['supplier']),
                'files' => fn () => $fabric_receiving->files()->get(),
                'directory' => DirectoryType::FabricsReceivings,
                'morph_class' => $fabric_receiving->getMorphClass(),
                'status_list' => StatusText::toValues(),
                'products' => fn (
                ) => ProductACResource::collection(resolve(ProductService::class)->autocompleteProducts()),
                'items' => fn (
                ) => FabricReceivingItemResource::collection($fabric_receiving->itemsWithProduct()->get()),
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
        FabricReceiving $fabric_receiving,
        FabricReceivingConfirmed $stockConfirmed,
        RecordAction $recordAction,
        UpdateFabricReceivingTotal $updateStockTotal
    ): RedirectResponse {
        $this->authorize('edit', $fabric_receiving);
        $data = $request->validate([
            'bilti_no' => ['required_if:status,'.StatusText::Close->value],
            'lot_no' => ['required_if:status,'.StatusText::Close->value],
            'status' => ['required', 'string', Rule::in(StatusText::toValues())],
            'info' => ['nullable', 'array'],
        ]);

        DB::transaction(function () use ($recordAction, $stockConfirmed, $updateStockTotal, $fabric_receiving, $data, $request) {
            unset($data['files']);
            $fabric_receiving->fill($data);
            if ($fabric_receiving->isClosed() && ! $fabric_receiving->transaction_date) {
                $fabric_receiving->transaction_date = today();
            }
            $fabric_receiving->save();
            $updateStockTotal->handle($fabric_receiving);
            if ($fabric_receiving->isClosed()) {
                $stockConfirmed->handle($fabric_receiving);
                $recordAction->handle($fabric_receiving, $request->user(), 'Stock receiving confirmed');
            }
        });

        return Redirect::route('purchases.fabric-receivings.index')
            ->with(['success' => 'Fabric receiving updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        FabricReceiving $fabric_receiving,
        Request $request,
        RecordAction $recordAction
    ): RedirectResponse {
        $this->authorize('delete', $fabric_receiving);
        $fabric_receiving->delete();
        $fabric_receiving->update(['status' => 'Cancel']);
        FabricReceivingItem::query()
            ->where('fabric_receiving_id', $fabric_receiving->id)
            ->update(['status' => 2]);
        $recordAction->handle($fabric_receiving, $request->user(), 'Stock receiving deleted');

        return Redirect::route('purchases.fabric-receivings.index')
            ->with(['success' => 'Fabric receiving deleted successfully']);
    }
}
