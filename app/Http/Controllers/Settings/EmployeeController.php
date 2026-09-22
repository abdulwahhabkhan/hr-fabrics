<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Attendance\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $employees = Employee::query()
            ->filter($request->only('search'))
            ->oldest('name')
            ->paginate(20)
            ->appends($request->only('search'));

        return Inertia::render('Settings/Employee/EmployeeIndex', [
            'employees' => $employees,
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()->route('settings.employees.index')->with('success', 'Employee deleted.');
    }
}
