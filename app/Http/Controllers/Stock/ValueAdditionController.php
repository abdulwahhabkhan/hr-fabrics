<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesIndexFilters;
use App\Models\Accounts\Account;
use App\Models\Stock\ValueAddition;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ValueAdditionController extends Controller
{
    use HandlesIndexFilters;

    protected string $sessionKey = 'value.additions';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
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
    public function create(): Response
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
}
