<?php

namespace App\Http\Controllers\Purchases;

use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\SupplierRequest;
use App\Models\Accounts\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as ResponseStatus;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        $rows = Account::query()->suppliers()
            ->filterContain('name', $request->input('search'))
            ->orderby('updated_at', 'desc')
            ->paginate()->appends($request->all());

        return Inertia::render(
            'Purchases/Suppliers/SupplierIndex',
            [
                'rows' => $rows,
                'filters' => $request->only('search'),
            ]
        );
    }

    public function store(SupplierRequest $request): JsonResponse
    {
        $request->validated();
        $data = $request->only('name', 'address');
        $data['type'] = AccountType::Supplier;
        $data['created_by'] = $request->user()->id;
        Account::create($data);

        return response()->json([
            'message' => 'The new supplier created successfully',
        ], ResponseStatus::HTTP_CREATED);
    }

    public function edit(Account $supplier): JsonResponse
    {
        return response()->json([
            'detail' => $supplier,
        ]);
    }

    public function update(SupplierRequest $request, Account $supplier): JsonResponse
    {
        $request->validated();
        $data = $request->only('name', 'address');
        $supplier->update($data);

        return response()->json([
            'message' => 'Supplier updated successfully',
        ], ResponseStatus::HTTP_ACCEPTED);
    }
}
