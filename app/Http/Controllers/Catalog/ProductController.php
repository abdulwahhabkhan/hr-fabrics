<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ProductRequest;
use App\Models\Accounts\Account;
use App\Models\Catalog\Brand;
use App\Models\Catalog\Finish;
use App\Models\Catalog\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $products = Product::with(['brand', 'vendor'])
            ->filterContain('name', $request['product_name'] ?? null)
            ->when($request->input('has_vendor'), function ($query, $val) {
                if ($val === 'yes') {
                    $query->whereNotNull('vendor_id');
                } else {
                    $query->whereNull('vendor_id');
                }
            })
            ->orderby('updated_at', 'desc')
            ->paginate()
            ->appends($request->all());

        return Inertia::render(
            'Catalog/Products/ProductIndex',
            [
                'products' => $products,
                'filters' => $request->only(['product_name', 'vendor_name', 'has_vendor']),
                'canAdd' => $request->user()->can('catalog.products.store'),
                'canUpdate' => $request->user()->can('catalog.products.update'),
                'canDelete' => $request->user()->can('catalog.products.destroy'),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $brands = Brand::all();
        $finish = Finish::getFinishList();
        $vendors = Account::query()
            ->suppliers()
            ->orderBy('name')
            ->get();

        return Inertia::render(
            'Catalog/Products/ProductForm',
            [
                'product' => null,
                'brands' => $brands,
                'finishes' => $finish,
                'vendors' => $vendors,
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['size'] ??= 0;
        Product::query()->create($data);

        return Redirect::route('catalog.products.index')->with(['success' => 'Product created successfully']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): Response
    {
        $brands = Brand::all();
        $finish = Finish::getFinishList();
        $vendors = Account::query()
            ->suppliers()
            ->orderBy('name')
            ->get();

        return Inertia::render(
            'Catalog/Products/ProductForm',
            [
                'product' => $product->load(['brand', 'vendor']),
                'brands' => $brands,
                'finishes' => $finish,
                'vendors' => $vendors,
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $data['size'] ??= 0;
        $product->update($data);

        return Redirect::route('catalog.products.index')->with(['success' => 'Product updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return Redirect::route('catalog.products.index')->with('success', 'Product deleted.');
    }
}
