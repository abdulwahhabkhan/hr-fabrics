<?php

namespace App\Http\Controllers\Sales;

use App\Enums\AccountType;
use App\Enums\DiscountType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\AccountsToOptions;
use App\Http\Requests\Sales\CustomerRequest;
use App\Http\Resources\Sales\CustomerResource;
use App\Models\Accounts\Account;
use App\Models\Catalog\Brand;
use App\Models\City;
use Arr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    use AccountsToOptions;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only('search', 'agent_id', 'city', 'status', 'credit');

        $customers = Account::query()
            ->customers()
            ->with('agent')
            ->when($request->input('search'), function ($query, $search) {
                $query->where(fn ($where) => $where
                    ->whereRaw(
                        "LOWER(CONCAT(name, ' ', JSON_VALUE(address, '$.\"city\"'))) LIKE ?",
                        ['%'.mb_strtolower($search).'%']
                    )
                    ->orWhereLike('name_urdu', "%{$search}%")
                    ->orWhereLike('phone', "%{$search}%")
                );
            })
            ->filterWhere('agent_id', $request->input('agent_id'))
            ->when($request->input('city'), function ($query, $city) {
                $query->whereRaw("JSON_VALUE(address, '$.\"city\"') = ?", [$city]);
            })
            ->when(in_array($request->input('status'), ['active', 'suspended'], true), function ($query) use ($request) {
                $query->where('suspended', $request->input('status') === 'suspended');
            })
            ->when($request->filled('credit'), function ($query) use ($request) {
                $query->where('credit', $request->boolean('credit'));
            })
            ->orderby('updated_at', 'desc')
            ->paginate()
            ->appends($filters);

        return Inertia::render(
            'Sales/Customers/CustomerIndex',
            [
                'customers' => CustomerResource::collection($customers),
                'filters' => $filters,
                'agents' => $this->agentOptions(),
                'cities' => City::getAll(),
                'canAdd' => $request->user()->can('sales.customers.store'),
                'canUpdate' => $request->user()->can('sales.customers.update'),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $brands = Brand::orderBy('name')->get();
        $cities = City::getAll();
        $discountTypes = DiscountType::toOptions();

        return Inertia::render('Sales/Customers/CustomerForm',
            [
                'customer' => null,
                'agents' => $this->agentOptions(),
                'brands' => $brands,
                'cities' => $cities,
                'discountTypes' => $discountTypes,
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CustomerRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $agent = $request->input('agent');
        $data = Arr::except($validated, 'agent');
        $data['agent_id'] = $agent ? $agent['id'] : 0;
        $data['created_by'] = $request->user()->id;
        $data['type'] = AccountType::Customer;

        Account::create($data);

        return Redirect::route('sales.customers.index')
            ->with(['success' => 'Customer created Successfully']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Account $customer): Response
    {
        $brands = Brand::orderBy('name')->get();
        $cities = City::getAll();
        $discountTypes = DiscountType::toOptions();

        return Inertia::render('Sales/Customers/CustomerForm',
            [
                'customer' => $customer->load(['agent']),
                'agents' => $this->agentOptions(),
                'brands' => $brands,
                'cities' => $cities,
                'discountTypes' => $discountTypes,
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomerRequest $request, Account $customer): RedirectResponse
    {
        $validated = $request->validated();
        $agent = $request->input('agent');
        $data = Arr::except($validated, 'agent');
        $data['agent_id'] = $agent ? $agent['id'] : 0;

        $customer->update($data);

        return Redirect::route('sales.customers.index')
            ->with(['success' => 'Customer updated Successfully']);
    }

    /**
     * Suspend the specified customer.
     */
    public function suspend(Account $customer): RedirectResponse
    {
        // Check if customer is already suspended
        if ($customer->suspended) {
            return Redirect::route('sales.customers.index')
                ->with(['error' => 'Customer is already suspended']);
        }

        // Update customer suspension status
        $customer->update([
            'suspended' => true,
            'suspended_at' => now(),
            'credit' => false,
            'limit' => null,
        ]);

        return Redirect::route('sales.customers.index')
            ->with(['success' => 'Customer suspended successfully']);
    }

    public function activate(Account $customer): RedirectResponse
    {
        // Check if customer is already activated
        if (! $customer->suspended) {
            return Redirect::route('sales.customers.index')
                ->with(['error' => 'Customer is already activated']);
        }

        // Update customer suspension status
        $customer->update([
            'suspended' => false,
            'suspended_at' => null,
        ]);

        return Redirect::route('sales.customers.index')
            ->with(['success' => 'Customer activated successfully']);
    }
}
