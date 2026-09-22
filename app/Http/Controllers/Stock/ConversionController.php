<?php

namespace App\Http\Controllers\Stock;

use App\Exceptions\ActionNotAllowedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\ConversionRequest;
use App\Http\Resources\Stock\ConversionResource;
use App\Jobs\ConversionTransaction;
use App\Models\Stock\Conversion;
use App\Models\Stock\Inventory;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ConversionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $items = Conversion::filter($request)->orderby('updated_at', 'desc')->paginate();

        return Inertia::render(
            'Stock/Conversion/ConversionIndex',
            [
                'items' => ConversionResource::collection($items),
                'filters' => $request->only('search'),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $products = resolve(ProductService::class)->autocompleteProducts();

        return Inertia::render(
            'Stock/Conversion/ConversionForm',
            [
                'products' => $products,
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws ActionNotAllowedException
     */
    public function store(ConversionRequest $request): RedirectResponse
    {
        throw ActionNotAllowedException::actionNotAllowed('Add conversion not supported any more.');
        $data = $request->only(['from', 'to']);
        $product = $request->input('product');
        $data['sku'] = $product['name'];
        $data['product_id'] = $product['id'];
        $data['created_by'] = $request->user()->id;
        $conversion = Conversion::query()->create($data);
        ConversionTransaction::dispatch($conversion);

        return Redirect::route('stocks.conversions.index')
            ->with(['success' => 'Conversion Created Successfully']);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Conversion $conversion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Conversion $conversion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Conversion $conversion)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws ActionNotAllowedException
     */
    public function destroy(Conversion $conversion): RedirectResponse
    {
        throw ActionNotAllowedException::actionNotAllowed('Delete Conversion not supported any more.');
        $conversion->delete();
        Inventory::query()->where([
            'type' => 'MC',
            'record_id' => $conversion->id,
            'product_id' => $conversion->product_id,
        ])->delete();

        return Redirect::route('stocks.conversions.index')
            ->with(['success' => 'Conversion Deleted Successfully']);
    }
}
