<?php

namespace App\Http\Controllers\Sales\Order;

use App\Actions\Outbound\SaleOrders\ConfirmOrder;
use App\Actions\Outbound\SaleOrders\CreateOrder;
use App\Actions\Outbound\SaleOrders\UpdateOrderTotal;
use App\Enums\DirectoryType;
use App\Enums\DiscountType;
use App\Enums\OrderPaid;
use App\Enums\PaymentMode;
use App\Enums\PurchaseType;
use App\Exceptions\UnableToAllocateStockException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesIndexFilters;
use App\Http\Resources\Catalog\ProductACResource;
use App\Http\Resources\Sales\OrderItemResource;
use App\Http\Resources\Sales\OrderResource;
use App\Models\Accounts\Account;
use App\Models\File;
use App\Models\Sales\Order;
use App\Services\AccountService;
use App\Services\ProductService;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class OrderController extends Controller
{
    use HandlesIndexFilters;

    protected string $sessionKey = 'sales.orders';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $this->filterSession($request, [
            'paid',
            'shipped',
            'invoice_no',
            'customer_name',
            'customer_city',
            'status',
        ]);
        $query = Order::query()->withCustomer();
        $query->filterContain('invoice_no', $request['invoice_no'] ?? null);
        $query->filterWhere('status', $request['status'] ?? null);
        $query->filterContain('name', $request['customer_name'] ?? null);
        $query->filterContain('address->city', $request['customer_city'] ?? null);
        $query->filterWhere('paid', $request['paid'] ?? null);
        $query->orderByDesc('updated_at');

        return Inertia::render(
            'Sales/Orders/OrderIndex',
            [
                'rows' => OrderResource::collection($query->paginate()->appends($filters)),
                'filters' => $filters,
                'canAdd' => $request->user()->can('sales.orders.store'),
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
            'Sales/Orders/OrderFormNew',
            [
                'customers' => $customers,
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, CreateOrder $createOrder): RedirectResponse
    {
        $validate = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:accounts,id'],
            'customer' => ['required', 'array'],
        ]);

        // Check if a customer is suspended
        $customer = Account::find($validate['customer_id']);
        if ($customer && $customer->suspended) {
            return Redirect::back()
                ->withErrors(['customer' => 'This customer is suspended and cannot place orders.'])
                ->withInput();
        }

        // $order = Order::saveOrder($validate);
        $order = $createOrder->handle($validate);

        return Redirect::route('sales.orders.edit', $order->id)
            ->with(['success' => 'Order Created Successfully']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order, Request $request): Response
    {
        $data = $order->load(['customer', 'user', 'items.product']);
        $data['customer_discount'] += $data['discount_on_total'];
        $total_summary = $data->items->groupBy('unit')->map(fn ($item) => $item->sum('qty'));

        return Inertia::render(
            'Sales/Orders/OrderView',
            [
                'order' => $data,
                'transaction_date' => $data->transaction_display_date->toDateString(),
                'balance' => $order->balance,
                'net_balance' => $order->net_balance,
                'total_summary' => $total_summary,
            ]
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order): Response|RedirectResponse
    {
        if (auth()->user()->cannot('update', $order)) {
            return Redirect::route('sales.orders.index')
                ->with(['error' => 'You cannot edit confirm order']);
        }

        $data = $order->load(['customer']);
        $balance = resolve(AccountService::class)->getAccountBalance($order->customer_id);
        $data['customer']['balance'] = $balance > 0 ? $balance : -1 * $balance;
        $data['customer']['customer_id'] = $order->customer_id;
        $data['customer']['customer_name'] = $order->customer->name;
        $data['customer']['city'] = $order->customer->address['city'] ?? '';
        $data['discount_label'] = $order->discount_label;
        $items = $order->itemsWithProduct;

        return Inertia::render(
            'Sales/Orders/OrderForm',
            [
                'order' => $data,
                'products' => ProductACResource::collection(resolve(ProductService::class)->availableProducts()),
                'items' => OrderItemResource::collection($items),
                'types' => PurchaseType::toValues(),
                'discountTypes' => DiscountType::toOptions(),
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     *
     *
     * @throws Throwable
     */
    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'payment_mode' => ['required', Rule::enum(PaymentMode::class)],
            'purchase_type' => ['required', Rule::enum(PurchaseType::class)],
            'paid' => ['bool'],
            'discount_rate' => ['numeric'],
            'discount_type' => ['required'],
            'expenses' => ['numeric'],
            'discount_on_total' => ['numeric'],
            'expenses_detail' => ['string', 'nullable'],
            'status' => ['required', 'numeric'],
            'customer' => ['required'],
            'customer.id' => ['required', Rule::exists(Account::class, 'id')],
        ]);
        $data['paid'] = OrderPaid::UnPaid;
        if ($request->input('payment_mode') === PaymentMode::Cash->value) {
            $data['paid'] = OrderPaid::Paid;
        }
        $customer = $data['customer'];
        unset($data['customer']);
        $data['customer_id'] = $customer['id'];
        $wasClosed = $order->isClosed();
        $order->fill($data);
        if ($order->isClosed() && $order->transaction_date === null) {
            $order->transaction_date = today();
        }
        try {
            DB::transaction(function () use ($order, $wasClosed) {
                $order->save();
                resolve(UpdateOrderTotal::class)->handle($order);
                if ($order->isClosed() && ! $wasClosed) {
                    resolve(ConfirmOrder::class)->handle($order, auth()->user());
                }

            });
        } catch (UnableToAllocateStockException $e) {
            throw ValidationException::withMessages([
                'product' => $e->getMessage(),
            ]);
        }

        return Redirect::route('sales.orders.index')
            ->with(['success' => 'Order updated successfully']);
    }

    public function orderItems(Order $order): JsonResponse
    {
        $items = $order->itemsWithProduct()->get();

        return response()->json([
            'items' => OrderItemResource::collection($items),
        ]);
    }

    public function gatePass(Order $order)
    {
        if (auth()->user()->cannot('gate-pass', $order)) {
            return Redirect::route('sales.orders.edit', $order->id)
                ->with(['error' => 'Please confirm & close the order']);
        }
        $data = $order->load(['customer', 'user', 'items.product']);

        return Inertia::render(
            'Sales/Orders/OrderGatePass',
            [
                'order' => $data,
                'transaction_date' => $data->transaction_display_date->toDateString(),
            ]
        );
    }

    public function bilti(Order $order)
    {
        if (! $order->isClosed()) {
            return Redirect::route('sales.orders.index', $order->id)
                ->with(['error' => 'Please to upload bilti confirm & close the order']);
        }
        $data = $order;

        return Inertia::render(
            'Sales/Orders/OrderBiltiForm',
            [
                'order' => $data,
                'directory' => DirectoryType::SalesBilties,
                'transaction_date' => $data->transaction_display_date->toDateString(),
                'attachments' => $order->files()->orderBilti()->get(),
            ]
        );
    }

    public function biltiUpload(Order $order, File $file): RedirectResponse
    {
        $file->fileable()->associate($order);
        $file->save();

        return Redirect::route('sales.orders.index')
            ->with(['success' => 'Bilti file updated successfully']);
    }
}
