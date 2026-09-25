<?php

namespace App\Http\Controllers\Stock;

use App\Exceptions\ActionNotAllowedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\ConversionRequest;
use App\Http\Resources\Stock\ConversionResource;
use App\Models\Stock\Conversion;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws ActionNotAllowedException
     */
    public function destroy(Conversion $conversion): RedirectResponse
    {
        throw ActionNotAllowedException::actionNotAllowed('Delete Conversion not supported any more.');
    }
}
