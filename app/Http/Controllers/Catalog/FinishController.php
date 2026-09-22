<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\FinishRequest;
use App\Http\Resources\Catalog\FinishResource;
use App\Models\Catalog\Finish;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as ResponseStatus;

class FinishController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $rows = Finish::filterContain('name', $request['search'] ?? null)->orderby('updated_at', 'desc')->paginate();

        return Inertia::render(
            'Catalog/Finish/FinishIndex',
            [
                'rows' => FinishResource::collection($rows),
                'filters' => $request->only('search'),
                'canAdd' => $request->user()->can('catalog.finish.store'),
                'canUpdate' => $request->user()->can('catalog.finish.update'),
                'canDelete' => $request->user()->can('catalog.finish.destroy'),
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(FinishRequest $request): JsonResponse
    {
        $data = $request->validated();
        Finish::create($data);

        return response()->json([
            'message' => 'The new Finish created successfully',
        ], ResponseStatus::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Finish $finish)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Finish $finish): JsonResponse
    {
        return response()->json([
            'finish' => $finish,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FinishRequest $request, Finish $finish): JsonResponse
    {
        $data = $request->validated();
        $finish->update($data);

        return response()->json([
            'message' => 'Finish updated successfully',
        ], ResponseStatus::HTTP_ACCEPTED);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Finish $finish): RedirectResponse
    {
        $finish->delete();

        return Redirect::route('catalog.finish.index')
            ->with('success', 'Finish deleted.');
    }
}
