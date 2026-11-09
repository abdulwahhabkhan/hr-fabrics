<?php

namespace App\Http\Controllers\Stock;

use App\Actions\Stock\Conversions\CreateConversion;
use App\Actions\Stock\Conversions\DeleteConversion;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\ConversionRequest;
use App\Http\Resources\Catalog\ProductACResource;
use App\Http\Resources\Stock\ConversionResource;
use App\Models\Stock\Conversion;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ConversionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $items = Conversion::query()
            ->with('user:id,name')
            ->filter($request)
            ->latest('updated_at')
            ->paginate()
            ->appends($request->only('search', 'location'));

        return Inertia::render(
            'Stock/Conversion/ConversionIndex',
            [
                'items' => ConversionResource::collection($items),
                'filters' => $request->only('search', 'location'),
                'canAdd' => $request->user()->can('stocks.conversions.store'),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ProductService $productService): Response
    {
        return Inertia::render(
            'Stock/Conversion/ConversionForm',
            [
                'products' => ProductACResource::collection($productService->availableProducts()),
            ]
        );
    }

    /**
     * Cut thaan stock into suits or thaans of another length.
     *
     * @throws Throwable
     */
    public function store(ConversionRequest $request, CreateConversion $createConversion): RedirectResponse
    {
        $conversion = $createConversion->handle($request->validated(), $request->user());

        return Redirect::route('stocks.conversions.index')
            ->with(['success' => "Conversion of {$conversion->sku} saved"]);
    }

    /**
     * Reverse a conversion and put the thaan stock back.
     *
     * @throws Throwable
     */
    public function destroy(Conversion $conversion, DeleteConversion $deleteConversion): RedirectResponse
    {
        $deleteConversion->handle($conversion);

        return Redirect::route('stocks.conversions.index')
            ->with(['success' => 'Conversion deleted, the thaan stock is back in stock']);
    }
}
