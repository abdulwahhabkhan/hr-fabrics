<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesIndexFilters;
use App\Models\Accounts\Account;
use App\Models\Stock\ValueAddition;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class ValueAdditionController extends Controller
{
    use HandlesIndexFilters;

    protected string $sessionKey = 'value.additions';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): \Inertia\Response
    {
        $filters = $this->filterSession($request, ['lot_number', 'vendor_name']);
        $values = ValueAddition::dataList($filters)->paginate();

        return Inertia::render(
            'Stock/ValueAddition/ValueAdditionIndex',
            [
                'items' => $values,
                'filters' => $request->only('search'),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): \Inertia\Response
    {
        $accounts = Account::material()->orderBy('name')->get();

        return Inertia::render(
            'Stock/ValueAddition/ValueAdditionForm',
            [
                'accounts' => $accounts,
                'data' => [],
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): Response
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ValueAddition $valueAddition): Response
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ValueAddition $valueAddition): Response
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ValueAddition $valueAddition): Response
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ValueAddition $valueAddition): Response
    {
        //
    }
}
