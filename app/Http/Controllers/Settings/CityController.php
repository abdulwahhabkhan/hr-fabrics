<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $cities = City::query()->filter($request)->oldest('name')->paginate(20)->appends($request->only('search'));

        return Inertia::render('Settings/City/CityIndex',
            [
                'cities' => $cities,
                'filters' => $request->only('search'),
            ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Settings/City/CityForm',
            [
                'city' => null,
            ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'max:100',
                Rule::unique(City::tName(), 'name'), ],
            'name_urdu' => ['required', 'max:100'],
        ]);
        City::create($data);

        return Redirect::route('settings.cities.index')->with(['success' => 'City created successfully']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(City $city): Response
    {
        return Inertia::render('Settings/City/CityForm',
            [
                'city' => $city,
            ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, City $city): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'max:100', Rule::unique(City::tName(), 'name')->ignore($city)],
            'name_urdu' => ['required', 'max:100'],
        ];
        $data = $request->validate($rules);
        $city->update($data);

        return Redirect::route('settings.cities.index')->with(['success' => 'City updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(City $city): RedirectResponse
    {
        $city->delete();

        return Redirect::route('settings.cities.index')->with('success', 'City deleted.');
    }
}
