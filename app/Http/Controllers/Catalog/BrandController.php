<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\BrandRequest;
use App\Http\Resources\Catalog\BrandResource;
use App\Models\Catalog\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as ResponseStatus;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $brands = Brand::with(['user'])
            ->filterContain('name', $request['search'] ?? null)
            ->paginate();

        return Inertia::render(
            'Catalog/Brands/BrandIndex',
            [
                'brands' => BrandResource::collection($brands),
                'filters' => $request->only('search'),
                'canAdd' => $request->user()->can('catalog.brands.store'),
                'canUpdate' => $request->user()->can('catalog.brands.update'),
                'canDelete' => $request->user()->can('catalog.brands.destroy'),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(BrandRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        Brand::create($data);

        return response()->json([
            'message' => 'The new brand created successfully',
        ], ResponseStatus::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @return void
     */
    public function show(Brand $brand)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Brand $brand): JsonResponse
    {
        return response()->json([
            'brand' => $brand,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BrandRequest $request, Brand $brand): JsonResponse
    {
        $brand->update($request->validated());

        return response()->json([
            'message' => 'Brand updated successfully',
        ], ResponseStatus::HTTP_ACCEPTED);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return Redirect::route('catalog.brands.index')->with('success', 'Brand deleted.');
    }
}
